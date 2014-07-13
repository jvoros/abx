<?
require_once('rb.php');

R::setup('sqlite:abx-db'); //connect redbean to the database

$ver = R::dispense('version');
$ver->version = 1;
$ver_id = R::store($ver);
echo "Vesion ID: ".$ver_id;


?>