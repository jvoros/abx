<?
require_once('rb.php');

R::setup('sqlite:abx-db'); //connect redbean to the database

$body = simplexml_load_file('abx.xml'); //pull in the xml data

foreach($body as $o) :

    $organ = R::dispense('organ');
    $organ->name = (string)$o->name;

    foreach ($o->infections->infection as $i) :

        $infection = R::dispense('infection');
        $infection->title = (string)$i->title;
        $infection->subtitle = (string)$i->subtitle;

        foreach ($i->treatments->treatment as $t) :
            
            $treatment = R::dispense('treatment');
            $treatment->location = (string)$t->location;
            $treatment->first = (string)$t->first;
            $treatment->alt = (string)$t->alt;
            $treatment->notes = (string)$t->notes;
                        
            $infection->ownTreatment[] = $treatment;            
            $treatment_id = R::store($treatment);
            
            
        endforeach; // treatments/treatment

        $organ->ownInfection[] = $infection;
        $infection_id = R::store($infection);

    endforeach; //infections/infection

    $organ_id = R::store($organ);

endforeach; // body/organs

echo "Complete. <a href='http://localhost/sites/DHREM/abx-crud/phpliteadmin.php'>Did it work?</a>";

?>