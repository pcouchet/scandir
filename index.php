<?php
include('scandir.php');
define('SERVER','localhost');
define('BASE','scandir');
define('USER','root');
define('PASSWD','');
set_time_limit(0);
$rootDir='../scandir';

if(isset($_GET['scanDir'])) $currentDir=$_GET['scanDir']; else $currentDir=$rootDir;
$tableDir='dir';
//$fieldsTableDir=array('id','dirname','reallevel','rellink','realpath','filenumber','size','c-date');
$fieldsTableDir=array('id','dirname','reallevel','rellink','filenumber','size','c-date');
$tableFile='file';
$fieldsTableFile=array('id','dirname','filename','extension','reallevel','realpath','rellink','size','c-date');
$fieldsTableFile=array('id','dirname','filename','extension','reallevel','rellink','size','c-date','md5');
$linkedField='rellink';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?php echo $currentDir?></title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="//cdn.datatables.net/1.10.11/css/jquery.dataTables.min.css">
  <style>div.container {margin: 0 auto; max-width: 1200px;}</style>
  <script type="text/javascript" src="//code.jquery.com/jquery-1.12.0.min.js"></script>
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
<?php
// Fonctionnalités de scandir paramétrées en get
$a= new scanDir;
// Create index
if (isset($_GET['writeTables']) && $_GET['writeTables']=='go') $a->writeTables($currentDir, $tableDir, $tableFile);
if (isset($_GET['emptyTables'])) $a->emptyTables($currentDir, $tableDir, $tableFile);

// Title
$a->readDirTitle($rootDir, $currentDir, $tableDir, $tableFile);
if($currentDir==$rootDir) {$a->readHome();}
if(isset($_GET['readDirVolume']) && $_GET['readDirVolume']==true) $a->readDirVolume($currentDir);
if (isset($_GET['readTable'])){
  if ($_GET['readTable']=='dir') $a->readTable($currentDir, $tableDir, $fieldsTableDir, $linkedField);
  if ($_GET['readTable']=='file')  $a->readTable($currentDir, $tableFile, $fieldsTableFile, $linkedField);
}

?>
</div>
</body>
</html>
