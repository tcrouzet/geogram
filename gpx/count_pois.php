<?php

require_once('gpx.class.php');

include (__DIR__ . '/../vendor/autoload.php');

if(!empty($_POST)) extract($_POST);

function copyXML($Styles, &$document){
    foreach ($Styles as $style) {
        $styleDom = dom_import_simplexml($style);
        $styleXml = $styleDom->ownerDocument->saveXML($styleDom);
    
        $newStyleDom = dom_import_simplexml($document);
        $fragment = $newStyleDom->ownerDocument->createDocumentFragment();
        $fragment->appendXML($styleXml);
        $newStyleDom->appendChild($fragment);
    }
}

if(!empty($_FILES['kmlfile']['tmp_name'])){
    //var_dump($_FILES);exit;
    if(strpos($_FILES['kmlfile']['type'],".kml+xml")===0) exit("File: KML needed");
    $kmlfile=$_FILES['kmlfile']['tmp_name'];
    $kml = simplexml_load_file($kmlfile);
    //dump($kml);exit;

    $poi_count = count($kml->Document->Placemark);

    // Tableau pour regrouper les POIs par icône
    $layers = [];
    foreach ($kml->Document->Placemark as $placemark) {

        if (isset($placemark->styleUrl)) {
            $poi = gpx::kml_POI((string) $placemark->styleUrl);
            $layers[$poi][] = $placemark;
        }
    
    }
    $clayers = count($layers);

    // Trier les calques par ordre décroissant de leur nombre de POIs
    uasort($layers, function($a, $b) {
        return count($b) - count($a);
    });

    $newKml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"></kml>');
    $document = $newKml->addChild('Document');

    copyXML($kml->Document->Style, $document);
    copyXML($kml->Document->StyleMap, $document);

    // Ajouter les Placemarks regroupés par calque
    foreach ($layers as $icon => $placemarks) {
        $folder = $document->addChild('Folder');
        $icons = count($placemarks);
        if($icons >1) $s="s"; else $s="";
        $folder->addChild('name', $icons." ".$icon.$s);

        copyXML($placemarks, $folder);

    }

    // Sauvegarde du nouveau fichier KML
    header('Content-Type: application/vnd.google-earth.kml+xml');
    header('Content-Disposition: attachment; filename="new_kml_file.kml"');
    
    // Sortie du contenu du fichier KML
    echo $newKml->asXML();

}else{

    gpx::htmlHead();

?>
    <h1>KML POIs sorting</h1>
    <form action="" enctype="multipart/form-data" method="post">
    <p>Select a KML file<p>
    <input type="file" name="kmlfile" size="80" style="width:80%"><br/>

    <input type="submit" value="RUN">
    </form>

<?php

    gpx::htmlFooter();
}

?>