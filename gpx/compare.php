<?php

/*

http://localhost:8888/tcrouzet-lab/gpx/compare.php

*/
set_time_limit(0);

require_once('gpx.class.php');

/*require __DIR__.'/vendor/autoload.php';
use phpGPX\phpGPX;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Link;
use phpGPX\Models\Metadata;
use phpGPX\Models\Point;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;*/

if(!empty($_POST)) extract($_POST);

if(!empty($_FILES['gpxfile1']['tmp_name']) && !empty($_FILES['gpxfile2']['tmp_name'])){

    if( strpos($_FILES['gpxfile1']['type'],"pplication/octet-stream")===0 || strpos($_FILES['gpxfile2']['type'],"pplication/octet-stream")===0){
        exit("Need 2 GPX files");
    }

    $routefile1=$_FILES['gpxfile1']['tmp_name'];
    $route1 = simplexml_load_file($routefile1);
            
    $routefile2=$_FILES['gpxfile2']['tmp_name'];
    $route2 = simplexml_load_file($routefile2);

    $xml=gpx::compareGPX($route1->trk,$route2->trk);
    header("Content-Type: application/gpx+xml");
    header("Content-Disposition: attachment; filename=compare.gpx");

    echo $xml->asXML();

    exit();
}

gpx::htmlHead();
?>
    <h3>Compare 2 GPX files</h3>
    <form action="" enctype="multipart/form-data" method="post">
    GPX 1<br/>
    <input type="file" name="gpxfile1" size="80" style="width:80%"><br/>
    GPX 2<br/>
    <input type="file" name="gpxfile2" size="80" style="width:80%"><br/>
    <br/><br/>

    <input type="submit" value="Run">
    </form>

<?php

gpx::htmlFooter();

?>