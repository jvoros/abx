<?

require_once('lib/rb.php');
R::setup('sqlite:admin/abx-db'); //connect redbean to the database
$ver = R::load('version', 1);

if ($ver->version > $_GET['ver']) {
    echo "update";
} else {
    echo "current";
}

?>