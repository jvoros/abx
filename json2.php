<?
// INITIALIZE
date_default_timezone_set('America/Denver'); //timezone for modified date
require_once('lib/rb.php'); // Redbean ORM v3.4 for database handling
R::setup('sqlite:admin/abx-db'); //connect redbean to the database

// get the version number
$ver = R::load('version', 1);

// Organs array
$organs_dump = R::findAll('organ', ' ORDER BY orderid');
$organs = [];
foreach ($organs_dump as $organ) {
  $infections = R::find('infection', ' organ_id = ? ORDER BY orderid', array($organ->id));
  $infections = R::exportAll($infections, true, array('infection'));
  $organs[$organ->id] = array(
    'id'          => $organ->id,
    'name'        => $organ->name,
    'infections'  => $infections
  );
}

// Infections array
$infections_dump = R::findAll('infection');
$infections = [];
foreach ($infections_dump as $infection) {
  $treatments = R::find('treatment', ' infection_id = ? ORDER BY orderid', array($infection->id));
  $treatments = R::exportAll($treatments);
  $infections[$infection->id] = array(
    'id'          => $infection->id,
    'title'       => $infection->title,
    'subtitle'    => $infection->subtitle,
    'treatments'  => $treatments
  );
}

$e = array(
  'version'     => $ver->version,
  'modified'    => date('d M Y', strtotime($ver->modified)),
  'organs'      => $organs,
  'infections'  => $infections
);

// send back JSON encoded data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($e, JSON_PRETTY_PRINT);
