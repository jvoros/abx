<?php
// super basic auth

$config['admin_username'] = "admin";
$config['admin_password'] = "dhremabx";
 
 
if (!($_SERVER['PHP_AUTH_USER'] == $config['admin_username'] && $_SERVER['PHP_AUTH_PW'] == $config['admin_password'])) {
    header("WWW-Authenticate: Basic realm=\"Papermashup.com Demo Admin\"");
    header("HTTP/1.0 401 Unauthorized");
    echo 'Sorry, not authorized';
    exit;
}

date_default_timezone_set('America/Denver'); //timezone for modified date

// connect to dbase
require_once('../lib/rb.php');
R::setup('sqlite:abx-db'); //connect redbean to the database
$organs = R::findAll('organ', ' ORDER BY orderid');
$version = R::load('version', 1);

// generate JSON model


// FUNCTIONS

// get JSON for initial POST request
if (isset($_POST['getJSON'])) {
    $j['organs'] = R::exportAll($organs);
    $j['version'] = $version->export();
    $json = json_encode($j);
    echo $json;
    return;
}

// sorting
if (isset($_POST['reorder'])) {
    parse_str($_POST['reorder'], $reorder);
    $keys = array_keys($reorder);
    $table = $keys[0];
    foreach($reorder[$table] as $order => $id) {
        $o = R::load($table, $id);
        $o->orderid = $order;
        $return_id = R::store($o);
    }
    return;
}

// jeditable update
if (isset($_POST['value'])) {
    $value = $_POST['value'];
    list($table, $el, $id) = explode("_", $_POST['id']);
    $newRow = R::load($table, $id);
    $newRow->$el = $value; //htmlspecialchars($value, ENT_QUOTES);
    $newRow->modified = date("Ymd");
    $id = R::store($newRow);
    echo $value;
    return;
}

// new
if (isset($_GET['new'])) {
    list($owner, $id) = explode("_", $_GET['new']);
    switch($owner) {
        case "main":
            $organ = R::dispense('organ');
            $organ->orderid = count(R::findAll('organ'));
            $newid = R::store($organ);
            echo '{"item":"organ", "id":"'.$newid.'"}';
        break;
        
        case "organ":
            $organ = R::load('organ', $id);
            $infection = R::dispense('infection');
            $organ->ownInfection[] = $infection;
            $organid = R::store($organ);
            $newid = R::store($infection);
            echo '{"item":"infection", "id":"'.$newid.'"}';
        break;
        
        case "infection":
            $infection = R::load('infection', $id);
            $treatment = R::dispense('treatment');
            $infection->ownTreatment[] = $treatment;
            $infectionid = R::store($infection);
            $newid = R::store($treatment);
            echo '{"item":"treatment", "id":"'.$newid.'"}';
        break;
    }
    return;
}

// delete
if (isset($_POST['delete'])) {
    list($table, $id) =explode("_", $_POST['delete']);
    switch($table) {
        case "organ":
            $organ = R::load('organ', $id);
            $infections = $organ->ownInfection;
            foreach ($infections as $infection) {
                $treatments = $infection->ownTreatment;
                R::trashAll($treatments);
            }
            R::trashAll($infections);
            R::trash($organ);
        break;
        
        case "infection":
            $infection = R::load('infection', $id);
            $treatments = $infection->ownTreatment;
            R::trashAll($treatments);
            R::trash($infection);
        break;
        
        case "treatment":
            $treatment = R::load('treatment', $id);
            R::trash($treatment);
        break;
    }
}

// backup
if (isset($_POST['backup'])) {
    // update backup versions and dates
    $version->backup_version = $version->version;
    $version->backup_time = date("Y-m-d-H-i");
    $version_id = R::store($version);
    
    // generate JSON model
    $j['organs'] = R::exportAll($organs);
    $j['version'] = $version->export();
    $json = json_encode($j, JSON_PRETTY_PRINT);
    
    // save file
    $filename = 'backups/backup_JSON_'.$version->backup_time.'.json';
    file_put_contents($filename, $json);
    
    // return new version and date
    $data['version'] = $version->backup_version;
    $data['date'] = $version->backup_time;
    echo json_encode($data);
    return;
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Antibiogram Editing</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap -->
<link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
<link href="css/flat-ui.css" rel="stylesheet" media="screen">
<link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">

<!-- ADOBE EDGE FONT -->
<script src="//use.edgefonts.net/source-sans-pro.js"></script>
    
<style>
    body { padding-top: 60px; font-family: source-sans-pro, sans-serif; background-color: #ecf0f1; }
    .navbar-inverse .brand { border: 0; }
    .organ { margin-bottom: 1em; padding: 0.5em; border-bottom: 1px solid #bdc3c7;}
    .section-head { display: inline; outline: none;  }
    .organ .infections { display: none; margin: 20px 50px; }
    .handles { padding: 1em; margin-right: 1em; background-color: #bdc3c7; color: white; }
    .infection-title { color: #1abc9c; }
    .infection { margin-bottom: 1em; }
    .treatments { display: none; margin: 0px 50px 50px 50px;}
    .treatment { clear: left; margin-top: 1em; }
    .treatment h3 { display: inline; }
    .details { margin-top: 1em; }
    .txtitle { width: 100px; float: left;}
    .txdetail { margin: 0 0 10px 100px;}
    .delete { float: right; }
    .add { clear: left;  }
    #backup { margin: 2em; }

</style>

</head>
<body>    

    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
          <span class="brand">DHREM ABX DATA</span>
      </div>
    </div>
    
    <div id="main" class="container">
    </div>
    
    <!-- TEMPLATES -->
    <script id="mainTpl" type="text/template">
        <h2>Version <span id="version_version_1" class="edit">{{#version}}{{version}}{{/version}}</span></h2>
        <div id="organs" class="sort">
            {{>organs}}
        <button id="addOrgan" class="btn btn-primary btn-small add"><i class="icon-plus"></i> Add Organ</button>
        </div>
        <div id="backup"><a class="btn btn-warning" id="backupButton">Backup</a>{{#version}} Last Backed-up Version: {{backup_version}} on {{backup_time}}{{/version}}</div>
        
    </script>
    
    <script id="organTpl" type="text/template">
        {{#organs}}
        <div class="organ" id="organ_{{id}}">
            <i class="icon-reorder move handles"></i>
            <i class="icon-level-down open handles"></i>
            <button class="btn btn-danger btn-small delete"><i class="icon-remove"></i></button>
            
            <h1 class="section-head organ-title edit" id="organ_name_{{id}}">{{name}}</h1>
            <div class="infections sort">
                {{>infections}}
            <button class="btn btn-primary add"><i class="icon-plus"></i> Add Infection</button>
            </div>
            
        </div>
        {{/organs}}
    </script>
    
    <script id="infectionsTpl" type="text/template">
    {{#ownInfection}}
    <div class="infection" id="infection_{{id}}">   
        <i class="icon-reorder move handles"></i>
        <i class="icon-level-down open handles"></i>
        <span>
            <h3 class="section-head infection-title edit" id="infection_title_{{id}}">{{title}}</h3>
            <span class="edit" id="infection_subtitle_{{id}}">{{subtitle}}</span>
        </span>
        <button class="btn btn-danger btn-small delete"><i class="icon-remove"></i></button>
        
        <div class="treatments sort">
            {{>treatments}}
        <button class="btn btn-primary btn-small add"><i class="icon-plus"></i> Add Treatment</button>
        </div>
        
    </div>
    {{/ownInfection}}
    </script>
    
    <script id="treatmentsTpl" type="text/template">
        {{#ownTreatment}}
        <div class="treatment" id="treatment_{{id}}">
            <i class="icon-reorder move handles"></i>
            <h3 class="edit" id="treatment_location_{{id}}">{{location}}</h3>
            <button class="btn btn-danger btn-small delete"><i class="icon-remove"></i></button>
            <div class="details">
                <div><div class="txtitle" >FIRST LINE:</div><div class="txdetail edit" id="treatment_first_{{id}}">{{{first}}}</div></div>
                <div><div class="txtitle" >ALTERNATE:</div><div class="txdetail edit" id="treatment_alt_{{id}}">{{{alt}}}</div></div>
                <div><div class="txtitle" >DURATION:</div><div class="txdetail edit" id="treatment_duration_{{id}}">{{{duration}}}</div></div>
                <div><div class="txtitle" >NOTES:</div><div class="txdetail edit" id="treatment_notes_{{id}}">{{{notes}}}</div></div>
            </div>
        </div>
        {{/ownTreatment}}
    </script>
    
<script src="http://code.jquery.com/jquery.js"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jeditable.js"></script>
<script src="js/mustache.js"></script>

<script>
    
    // build from templates
    var json = null;
    $.ajax({
        async: false,
        type: 'POST',
        data: { getJSON: 'yes' },
        url: '',
        success: function(data){ json = data; },
        dataType: 'json'
    });

    var mainTpl = $('#mainTpl').html();
    var organsTpl = $('#organTpl').html();
    var infectionsTpl = $('#infectionsTpl').html();
    var treatmentsTpl = $('#treatmentsTpl').html();
    var partials = { "organs": organsTpl, "infections": infectionsTpl, "treatments": treatmentsTpl };
    var html = Mustache.to_html(mainTpl, json, partials);
    $('#main').html(html);

    // pointers
    $('.move').css('cursor', 'move');
    $('.open').css('cursor', 'pointer');    
    
    // sliders
    $('#main').on('click', '.open', function(){
            $(this).parent().children('.sort').slideToggle();
        });
        
    // sorting
    $('.sort').sortable({
        handle: ".move",
        update: function(event,ui){
            var io = $(this).sortable('serialize');
            $.post('', { reorder: io })
            .done(function(data){console.log(data);});
        }
    });
    
    // updating
    $('.edit').editable('');
 
    
    // creating
    $('#main').on('click', '.add', function(){
        var button = $(this);
        var parent = $(this).parent().parent();
        var parent_id = parent.attr('id');
        $.getJSON('', { new: parent_id })
        .done(function(data){
            console.log(data.item);
            switch(data.item)
            {
                case 'organ':
                    var organs = {"organs":[{"id":data.id, "name":"Click to edit"}]};
                    var html = Mustache.to_html(organsTpl, organs);
                    $('#organs button:last').before(html);
                break;
                
                case 'infection':
                    var infections = {"ownInfection":[{"id":data.id, "title":"Click to edit", "subtitle":"Click to Edit"}]};
                    var html = Mustache.to_html(infectionsTpl, infections);
                    button.before(html);
                break;
                    
                case 'treatment':
                    var treatments = {"ownTreatment":[{"id":data.id, "location":"Click to edit", "first":"Click to edit", "alt":"Click to edit", "notes":"Click to edit"}]};
                    var html = Mustache.to_html(treatmentsTpl, treatments);
                    button.before(html);
                break;                    
            }
            $('.edit').editable('');
            $('.sort').sortable('refresh');
        });
    });
    
    // deleting
    $('#main').on('click', '.delete', function(){
        // get the parent div and id
        var parent = $(this).parent()
        var id = parent.attr('id');
        // get the organ, infection or treatment from id
        var table = id.split("_");
        // set confirmation text based on organ, infxn, or tx
        var confirmText;
        switch(table[0])
        {
            case 'organ': confirmText = "Press 'OK' to delete the Organ System and all associated infections and treatments"; break;
            case 'infection': confirmText = "Press 'OK' to delete this Infection and all associated treatments"; break;
            case 'treatment': confirmText = "Press 'OK' to delete this treatment"; break;
        }
        // popup confirmation
        var c = confirm(confirmText);
        if (c == true) { 
            $.post("", {delete: id})
            .done(function(){parent.remove();});
        }
    });
    
    // backup
    $('#backupButton').on('click', function(e){
        $.post('', {backup: 'true'}, function(data){
            var msg = "BACKED UP. Last Backed-up Version: "+data.version+" on "+data.date;
            $('#backup').html(msg);
        }, 'json');
    });

</script>
</body>
</html>