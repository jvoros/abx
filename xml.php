<?

header("Content-type: text/xml"); 
require_once('lib/rb.php');
R::setup('sqlite:lib/abx-db'); //connect redbean to the database
$organs = R::findAll('organ', ' ORDER BY orderid');
$ver = R::load('version', 1);

$xml = '<?xml version="1.0"?>';
$xml .= '<body version="'.$ver->version.'">';

 
foreach ($organs as $organ) :
    
    $xml .= '<organ>';
    $xml .= '<name>' .$organ->name.'</name>';
    $xml .= '<infections>';
    
    $infections = R::find('infection', ' organ_id = ? ORDER BY orderid', array($organ->id));
    foreach ($infections as $infection) :

        $xml .= '<infection>';
        $xml .= '<title>'.$infection->title.'</title>';
        $xml .= '<subtitle>'.$infection->subtitle.' </subtitle>';
        $xml .= '<treatments>';

        $treatments = R::find('treatment', ' infection_id = ? ORDER BY orderid', array($infection->id));
        foreach ($treatments as $treatment) :
        
            $xml .='<treatment>';
            $xml .='<location>'.$treatment->location.' </location>';
            $xml .='<first>'.$treatment->first.' </first>';
            $xml .='<alt>'.$treatment->alt.' </alt>'; 
            $xml .='<notes>'.$treatment->notes.' </notes>';
            $xml .='</treatment>';

        endforeach; //treatments
    
        $xml .= '</treatments></infection>';
    
    endforeach; //infections
    
    $xml .= '</infections></organ>';

endforeach; //organs
$xml .= '</body>';
echo $xml;
?>