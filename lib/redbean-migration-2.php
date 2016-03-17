<?
require_once('rb.php');

R::setup('sqlite:../admin/abx-db'); //connect redbean to the database

$t = R::dispense('treatment');
$t->duration = 'duration';
R::store($t);
R::trash($t);
echo "added 'duration'";