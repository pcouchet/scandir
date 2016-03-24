<?php
define('SERVER','localhost');
define('BASE','scandir');
define('USER','root');
define('PASSWD','');
set_time_limit(10);

$scandir = '../bourbaki';
$tableDir='dir';
$fieldsTableDir=array('id','name','fullname','filenumber','size','c-date','note');
$tableFile='file';
$fieldsTableFile=array('id','name','fullname','size','c-date','extension','note');
$linkedField='name';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?php echo $scandir?></title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="//cdn.datatables.net/1.10.11/css/jquery.dataTables.min.css">
  <style>div.container {margin: 0 auto; max-width: 980px;}</style>
  <script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-1.12.0.min.js"></script>
  <script src="//cdn.datatables.net/1.10.11/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript">
  $(document).ready(function(){
    $('#myTable').DataTable({
      "scrollY": "800px",
      "scrollCollapse": true,
      "paging": false,
      "language": {
        "search": "Chercher",
        "info": "Items _START_ à _MAX_"
      }
    });
  });
  </script>
</head>
<body>
<div class="container">
<h2>Répertoire scanné : <?php echo $scandir ?></h2>
<?php
$a= new scanDir;
//$a->writeTables($scandir, $tableDir, $tableFile);
//$a->readTable($tableDir, $fieldsTableDir, $linkedField);
$a->readTable($tableFile, $fieldsTableFile, $linkedField);

// Afficher table
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
  function readTable($table, $fields, $linkedField){
    $sql='SELECT `'.implode('`, `',$fields).'` from `'.$table.'`';
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
    } catch (PDOException $e) {
        print "Erreur de lecture!: " . $e->getMessage() . "<br/>";
        die();
    }
  }
  function writeTables($scanDir, $tableDir, $tableFile){
    $dir = new RecursiveDirectoryIterator($scanDir);
    $objects = new RecursiveIteratorIterator($dir);
    foreach ($objects as $fileName => $file) {
      if(!preg_match('/\.\.$/',$fileName)){
        $fileName=preg_replace('/\\\/','/',$fileName);
        $cdate = date ("Y-m-d H:i:s", $file->getCTime());
        // réperoire
        if($file->isDir()){
          $folderSize=$this->folderSize($fileName);
          echo '<span style="color:red">D</span> ';
          $files = scandir($fileName);
          $fileNb = count($files)-2;
          echo ' <a href="'.$fileName.'">';
          echo $fileName.'</a> - '.$folderSize.' bytes, '.$fileNb.' fichiers et répertoires<br/>';
          $data=array(array('id'=>'','name'=>$fileName,'fullname'=>realpath($fileName),'filenumber'=>$fileNb,'size'=>$folderSize,'c-date'=>$cdate,'note'=>''));
          $this->writeTable($tableDir, $data);
        }
        // fichier
        else {

          echo 'F ';
          echo '<a href="'.$fileName.'">';
          echo $fileName.'</a> - '.$file->getSize().' bytes, '.$file->getExtension().'<br/>';
          $data=array(array('id'=>'','name'=>$fileName,'fullname'=>realpath($fileName),'size'=>$file->getSize(),'c-date'=>$cdate,'extension'=>$file->getExtension(),'note'=>''));
          $this->writeTable($tableFile, $data);
        }
      }
    }
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
  function folderSize($dir)
  {
      $size = 0;
      foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
          $size += is_file($each) ? filesize($each) : $this->folderSize($each);
      }
      return $size;
  }
}
?>
</div>
</body>
</html>
