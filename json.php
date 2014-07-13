<?
// INITIALIZE
date_default_timezone_set('America/Denver'); //timezone for modified date
require_once('lib/rb.php'); // Redbean ORM v3.4 for database handling
R::setup('sqlite:lib/abx-db'); //connect redbean to the database

// get data from database
$organs = R::findAll('organ', ' ORDER BY orderid');
// preload infections and treatments
R::preload($organs, 'infection, infection.treatment');
// get the version number
$ver = R::load('version', 1);

// convert to array with antibiogram data and version
$organs_array = R::exportAll($organs);
$e['version'] = $ver->version;
$e['ownOrgan'] = $organs_array;

// send back JSON encoded data
header('Content-Type: application/json');
echo json_encode($e, JSON_PRETTY_PRINT);
