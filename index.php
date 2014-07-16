<?php
/************************************************

ANTIBIOGRAM WEB APPLICATION
---------------------------
Developed by: Jeremy Voros <jeremyvoros@gmail.com>


NOTES:
- NOTES in php header so it doesn't show up on HTML page ;)
- can't use JQM "Back" button because of bug in mobile Safari when using cache manifest

************************************************/

// INITIALIZE

date_default_timezone_set('America/Denver'); //timezone for modified date
require_once('lib/rb.php'); // Redbean ORM v3.4 for database handling
R::setup('sqlite:admin/abx-db'); //connect redbean to the database

// get data from database
$organs = R::findAll('organ', ' ORDER BY orderid');
R::preload($organs, 'infection, infection.treatment');
$ver = R::load('version', 1);

?>

<!DOCTYPE html>
<!--DYNAMICALLY GENERATED CACHE MANIFEST
    be sure to modify manifest to exclude files not needed for mobile, 
    like /admin
-->
<html manifest="manifest.php">
<head>
  
  <title>Abx:DH</title>
  
  <!--  MOBILE WEB APP META-->
  <meta name="apple-mobile-web-app-title" content="Abx:DH">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta http-equiv="cleartype" content="on">
  <link rel="apple-touch-icon-precomposed" href="apple-touch-icon.png">
  <link rel="icon" href="apple-touch-icon.png">
  
  <!--  JQM LOCAL for JS and CSS-->
  <link rel="stylesheet" href="css/jquery.mobile.flatui.css" />
  <script src="js/jquery-1.11.1.min.js"></script>
  <script src="js/jquery.mobile-1.3.2.min.js"></script>
  
  <!--  EXTRA LIBRARIES-->
  <script src="js/fastclick.js"></script>
  <script src="https://cdn.flurry.com/js/flurry.js"></script> 
  <script>
    // ENABLE FAST-CLICK
    $(function() {
      FastClick.attach(document.body);
    });  
    
    // LOG EVENTS TO FLURRY
    FlurryAgent.startSession("PXRS2Q9VP4DWCC289H2N");
    
    $(document).on("pageshow", ".flurry", function(event) {
      var event = $(this).find('.ui-header').find('h1').html();
      console.log('Event: ' + event);
      FlurryAgent.logEvent(event);
    });
  </script>
  
  <!--  CUSTOM STYLES-->
  <style>
    /* USED IF TRANSLUCENT IPHONE STATUS BAR
        .ui-mobile .ui-header  { padding-top: 15px; }
        .ui-header .ui-btn-left { top: 18px; }
    */
    /*    remove link underlines*/
    a { text-decoration: none; }
    
    /*    border around back button*/
    .ui-bar-a .ui-btn-up-a { border: 1px solid #34495e; }
    
    /*    bottom border on list items and word wrapping in list items*/
    .ui-listview>li { border-bottom: 1px solid white; }
    .ui-listview>li p { font-size: 0.85em; white-space: normal; }
  </style>

</head>
<body>

  <!--  STARTING PAGE-->
  <div data-role="page" id="home">
    <div data-role="header">
      <h1>Organs</h1>
    </div>
    <div class="ui-content">
      <ul data-role="listview">
        
        <? foreach($organs as $organ) : ?>
        <li><a href="#organ-<?=$organ->id?>" data-transition="slide"><?=$organ->name?></a></li>
        <? endforeach; ?>
        
        <li data-role="list-divider"></li>
        <li><a href="#about" data-transition="flip">About</a></li>
        
      </ul>
    </div>
  </div>
  
  
  <!-- ORGAN PAGES, ONE FOR EACH ORGAN, LISTS INFECTIONS -->
  <? foreach($organs as $organ) : ?>
  <div data-role="page" id="organ-<?=$organ->id?>">
    <div data-role="header">
      <a data-role="button" href="#home" data-transition="slide" data-direction="reverse">Back</a>
      <h1><?=$organ->name?></h1>
    </div>
    <div class="ui-content">
      <ul data-role="listview">

        <? $infections = $organ->ownInfection; foreach ($infections as $infection) : ?>
        <li>
          <a href="#infection-<?=$infection->id?>" data-transition="slide">
            <h2><?=$infection->title?></h2>
            <p><?=$infection->subtitle?></p>
          </a>
        </li>
        <? endforeach;?>

      </ul>
    </div>
  </div>
  <? endforeach;?>
  <!--  END ORGAN PAGES-->
  
  
  <!-- INFECTION PAGE, ONE FOR EACH INFECTION, LISTS TREATMENTS -->
  <? foreach($organs as $organ) : $infections = $organ->ownInfection; foreach($infections as $infection) : ?>
  <div data-role="page" id="infection-<?=$infection->id?>" class="flurry">
    <div data-role="header">
      <a data-role="button" href="#organ-<?=$organ->id?>" data-transition="slide" data-direction="reverse">Back</a>
      <h1><?=$infection->title?></h1>
    </div>
    <div class="ui-content">
      <ul data-role="listview">
      <? $treatments = $infection->ownTreatment; foreach($treatments as $treatment) :?>
      <li data-role="list-divider"><?=$treatment->location?></li>
      <li><h3>First Line</h3><p><?=$treatment->first?></p></li>
      <li><h3>Alternate</h3><p><?=$treatment->alt?></p></li>
      <li><h3>Notes</h3><p><?=$treatment->notes?></p></li>
      <? endforeach; ?>
      </ul>
    </div>
  </div>
  <? endforeach; endforeach; ?>
  <!--  END INFECTION PAGES -->
  
  
  <!--  ABOUT-->
  <div data-role="page" id="about" class="flurry">
    <div data-role="header">
      <a data-role="button" href="#home" data-transition="flip">Back</a>
      <h1>About</h1>
    </div>

    <div class="ui-content">
      <h2>Abx:DH</h2>
        <p>Antibiogram version: <?=$ver->version?></p>
      <p><i>Software Version 1.0</i> - July 2014</p>
      <p>Developed by: <b>Jeremy Voros, MD</b></p>
      <p>Conceptual assistance by: <b>Dave Bosch, DO</b></p>
      <p>Institutional support from: Jeffery Sankoff, MD, Michelle Haas, MD, Tim Jenkins, MD</p>
      <p>Questions? Email: <b><a href="mailto:jeremy.voros@denverem.org">jeremy.voros@denverem.org</a></b></p>
      
      <div data-role="collapsible">
        <h3>Denver Health Legal Disclaimer</h3>
        <p>Antibiotic Stewardship Application - DISCLAIMER - December 31, 2013</p>
        <p>This Antibiotic Stewardship Application ("Application") has been created by the Denver Health and Hospital Authority ("Denver Health") and is intended for informational purposes only. Although Denver Health attempts to keep this information as accurate as possible, Denver Health makes no guarantees or warranties of any kind, express or implied, with respect to the use of this Application.</p>
        <p>This Application is not intended to be, nor should it be used as a substitute for, the professional medical advice or analysis required when prescribing an antibiotic for a Denver Health patient or any patient outside of Denver Health. Use of this Application is not intended to, nor does it create, a physician-patient or healthcare provider-patient relationship between Denver Health and the user or the user's patient.  Application users assume full responsibility for any actions taken on the basis of the information obtained from use of the Application and agree that Denver Health bears no responsibility for any claim, loss or damage caused by or related to its use.</p>
        <p>Since Denver Health has no legal obligation to update the information provided on this Application, Denver Health cannot ensure that all information reflects the most up-to-date information regarding prescribing antibiotics. Denver Health may make changes or improvements to this Application at any time without notice or announcement.  Application users outside of Denver Health should consult their local facility policies and procedures regarding antibiotic use.</p>  
        <p>If you have any questions about this disclaimer or any other information contained on this Application, you can contact Denver Health Infectious Disease Department.</p>
      </div>
      
    </div>
  </div>  
  <!--  END ABOUT-->
  
</body>
</html>