<?php
// Read a directory, write content in mysql tables, read the tables
class scanDir {
  var $dbh;
  function __construct(){
    // Connexion
    try {$this->dbh = new PDO('mysql:host='.SERVER.';dbname='.BASE, USER, PASSWD); }
    catch (PDOException $e) {
      print "Erreur de connexion!: " . $e->getMessage() . "<br/>";
      die();
    }
  }
  function readHome(){
    echo '<div style="display: inline-block;"><h1>ScanDir Home</h1>';
    echo '<p>Programme de scan et d\'indexation des répertoires d\'un serveur publiquement accessible. ';
    echo 'Placer les fichiers de ScanDir dans un répertoire particulier, sous la racine par exemple, dans le répertoire ';
    echo 'scandir. Explorez les répertoires parents et les sous-répertoires avec les listes déroulantes situées ';
    echo 'dans le menu de gauche. Vous pouvez en haut à gauche visionner les index des répertoires et fichiers lorsque ';
    echo 'ceux-ci ont été créés.</p> ';
    echo '<p>Il est possible dans la partie centrale de supprimer les index ou de les créer de nouveau.</p></div>';
  }
  function readDirTitle($rootDir, $scanDir, $tableDir, $tableFile){
    echo '<div style="float:left;margin-right:1em;width:25em;">';
    echo '<a href="./">Home</a><br/>';
    $scanTable=explode("/",$scanDir);
    $navDir='';
    $navDirLink='../';
    foreach($scanTable as $key=>$value){
      if($key!=0){
        $navDirLink.=$value.'/';
        $before='<a href="./?scanDir='.rtrim($navDirLink,'\/').'">';
        $after='</a>';
      }
      else $before=$after='';
      $navDir.=$before.$value.$after.'/';
    }
    $navDirLink=rtrim($navDirLink,'\/');
    echo 'Explorer les répertoires parents :';
    $parentDir=explode('/',$scanDir,-1);
    $parentDir=implode('/',$parentDir);
    echo $this->readDir($parentDir);
    echo $navDir.'<br/>';
    echo 'Explorer les sous-répertoires :';
    echo $this->readDir($scanDir);
    $dirNb=$this->readNbTables($scanDir, $tableDir);
    $fileNb=$this->readNbTables($scanDir, $tableFile);
    echo '<a href="./?scanDir='.$navDirLink.'&amp;readTable=dir">Index des répertoires</a> ('.$dirNb.')<br/>';
    echo '<a href="./?scanDir='.$navDirLink.'&amp;readTable=file">Index des fichiers</a> ('.$fileNb.')<br/>';
    echo '</div>';
    echo '<h2>Répertoire scanné : '.$navDir.'</h2>';
    if($dirNb==0 && $fileNb==0){
      echo '<div>Absence d\'index.</div>';
      echo '<a href="?scanDir='.$scanDir.'&amp;readDirVolume=true">Calculer le nombre de fichiers et le volume ?</a><br/>';
    }
    else {
      $fileVol=$this->readFileVol($tableFile, $scanDir);
      echo '<p>'.$dirNb.' répertoires et '.$fileNb.' fichiers indexés, '.$this->fileSize($fileVol).'</p>';
      echo '<a href="?scanDir='.$scanDir.'&amp;emptyTables=true">Supprimer les index de ce répertoire</a>';
    }
  }
  function readDir($scanDir){
    echo '
    <form>
    <select name="scanDir" onChange="this.parentNode.submit()">
    ';
    echo '<option value="">Choisir</option>'."\n";
    if ($handle = opendir($scanDir)) {
      while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && is_dir($scanDir.'/'.$file)) {
          if (isset($_GET['scanDir'])) echo $_GET['scanDir'];
          $selected='';
          echo '<option value="'.htmlspecialchars($scanDir.'/'.$file).'">'.$scanDir.'/'.$file.'</option>'."\n";
        }
      }
      closedir($handle);
    }
    echo '
    </select>
    </form>
    ';
  }
  function readNbTables($scanDir, $table){
    try {
      $sql='SELECT COUNT(*) FROM `'.$table.'`';
      $sql.=' WHERE `rellink` LIKE \''.$scanDir.'%\'';
      $sth = $this->dbh->prepare($sql);
      $sth->execute();
      $result = $sth->fetch();
      $dbh = null;
    }
    catch (PDOException $e) {
      print "Erreur de lecture!: " . $e->getMessage() . "<br/>";
      die();
    }
    return $result[0];
  }
  function readFileVol($table, $scanDir){
    try {
      $sql='SELECT sum(`size`) FROM `'.$table.'`';
      $sql.=' WHERE `rellink` LIKE \''.$scanDir.'%\'';
      $sth = $this->dbh->prepare($sql);
      $sth->execute();
      $result = $sth->fetch();
      $dbh = null;
    }
    catch (PDOException $e) {
      print "Erreur de lecture!: " . $e->getMessage() . "<br/>";
      die();
    }
    return $result[0];
  }
  function readDirVolume($scanDir){
    $dirSize=0;
    $countDir=0;
    $countFile=0;
    $dir = new RecursiveDirectoryIterator($scanDir);
    $objects = new RecursiveIteratorIterator($dir);
    foreach ($objects as $fileName => $file) {
      if($file->isFile()){
        $dirSize+=$file->getSize();
        $countFile++;
      }
      elseif(!preg_match('/\.\.$/',$fileName)) {
        $countDir++;
      }
    }
    echo '<div>';
    echo 'Sous-répertoires : '.($countDir-1).'; ';
    echo 'Fichiers : '.$countFile.'; ';
    echo 'Taille : '.$this->fileSize($dirSize).';<br/>';
    echo '<a href="?scanDir='.$scanDir.'&amp;writeTables=go">Lancer l\'indexation ?</a><br/>';
    echo '</div>';
  }
  function fileSize($size){
    // Go, Mo, Ko conversion
    if ($size >= 1073741824){$size=round($size / 1073741824 * 100) / 100 . " Go"; }
    elseif ($size >= 1048576){$size=round($size / 1048576 * 100) / 100 . " Mo"; }
    elseif ($size >= 1024){$size=round($size / 1024 * 100) / 100 . " Ko"; }
    else {$size=$size." octets";}
    if($size==0){$size="-";}
    return $size;
  }
  function writeTables($scanDir, $tableDir, $tableFile){
    $dir = new RecursiveDirectoryIterator($scanDir);
    $objects = new RecursiveIteratorIterator($dir);
    foreach ($objects as $fileName => $file) {
      if(!preg_match('/\.\.$/',$fileName)){
        // shared
        $cdate = date ("Y-m-d H:i:s", $file->getCTime());
        $fileName=preg_replace('/\\\/','/',$fileName);
        $name=explode('/',$fileName);
        $realpath=realpath($fileName);
        if(preg_match("/\\//",$realpath)) $level=explode("/",$realpath);
        if(preg_match("/\\\/",$realpath)) $level=explode("\\",$realpath);
        // directory
        if($file->isDir()){
          $fileName=preg_replace('/.$/','',$fileName);
          $folderSize=$this->folderSize($fileName);
          echo '<span style="color:red">D</span> ';
          $files = scandir($fileName);
          $fileNb = count($files)-2;
          echo ' <a href="'.$fileName.'">';
          echo $fileName.'</a> - '.$folderSize.' bytes, '.$fileNb.' fichiers et répertoires<br/>';
          $data=array(array('id'=>'','dirname'=>$name[sizeof($name)-2],'reallevel'=>sizeof($level)-1,'realpath'=>$realpath,'rellink'=>$fileName,'filenumber'=>$fileNb,'size'=>$folderSize,'c-date'=>$cdate));
          $this->writeTable($tableDir, $data);
        }
        // file
        else {
          echo 'F ';
          echo '<a href="'.$fileName.'">';
          echo $fileName.'</a> - '.$file->getSize().' bytes, '.$file->getExtension().'<br/>';
          $data=array(array('id'=>'','dirname'=>$name[sizeof($name)-2],'filename'=>$name[sizeof($name)-1],'extension'=>$file->getExtension(),'reallevel'=>sizeof($level)-1,'realpath'=>$realpath,'rellink'=>$fileName,'size'=>$file->getSize(),'md5'=>md5_file($realpath),'c-date'=>$cdate));
          $this->writeTable($tableFile, $data);
        }
      }
    }
    echo '<h2>Fin d\'écriture</h2>';
  }
  function emptyTables($scanDir, $tableDir, $tableFile){
    try {
      // Effacer les anciennes occurences
      $sql='DELETE FROM `'.$tableDir.'` WHERE `rellink` LIKE \''.$scanDir.'%\'';
      $sth = $this->dbh->prepare($sql);
      $sth->execute();
      $result = $sth->fetch();
      $dbh = null;
      $sql='DELETE FROM `'.$tableFile.'` WHERE `rellink` LIKE \''.$scanDir.'%\'';
      $sth = $this->dbh->prepare($sql);
      $sth->execute();
      $result = $sth->fetch();
      $dbh = null;
    }
    catch (PDOException $e) {
      print "Erreur de lecture!: " . $e->getMessage() . "<br/>";
      die();
    }
    return $result[0];
  }
  function writeTable($table, $data){
    $sql='';
    foreach($data as $key=>$value){
      // Fabrication requête
      $sql='INSERT INTO `'.$table.'` (';
      $fields='';
      $values='';
      foreach($value as $key2=>$value2){
        $fields.='`'.$key2.'`, ';
        $values.='\''.addslashes($value2).'\', ';
      }
      $fields=rtrim($fields, ', ');
      $values=rtrim($values, ', ');
      $sql.=$fields.') VALUES ('.$values.');';
      //Lancement requête
      try {
        $req = $this->dbh->prepare($sql);
        $req->execute();
      } catch (PDOException $e) {
          print "Erreur de lecture!: " . $e->getMessage() . "<br/>";
          die();
      }
    }
  }
  function readTable($scanDir, $table, $fields, $linkedField){
    $sql='SELECT `'.implode('`, `',$fields).'` FROM `'.$table.'`';
    $sql.=' WHERE `rellink` LIKE \''.$scanDir.'%\'';
    try {
      echo '<table id="myTable" class="display" cellspacing="0" width="100%">';
      echo '<thead><tr>';
      foreach($fields as $field){
        echo '<th>'.$field.'</th>';
      }
      echo '</tr></thead>';
      echo '<tbody>';
      $i=0;
      foreach ($this->dbh->query($sql) as $row){
        ($i%2) ? $class='even' : $class='odd';
        echo '<tr class='.$class.'>';
        foreach($fields as $field){
          echo '<td>';
          if($field==$linkedField){
            $openLink='<a href="'.$row[$field].'" target="_blank">';
            $closeLink='</a>';
          }
          else $openLink=$closeLink='';
          echo $openLink.$row[$field].$closeLink;
          echo '</td>';
        }
        echo '</tr>';
      }
      echo '</tbody>';
      echo '</table>';
      $dbh = null;
    }
    catch (PDOException $e) {
        print "Erreur de lecture!: " . $e->getMessage() . "<br/>";
        die();
    }
  }
  function folderSize($dir)
  {
      $size = 0;
      foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
          $size += is_file($each) ? filesize($each) : $this->folderSize($each);
      }
      return $size;
  }
}
