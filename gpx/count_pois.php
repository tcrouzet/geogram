<?php

$debug = false;

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

function extractPlacemarks($element, &$layers) {
    if (isset($element->Placemark)) {
        foreach ($element->Placemark as $placemark) {
            if (isset($placemark->styleUrl)) {
                $poi = gpx::kml_POI((string) $placemark->styleUrl);
                $layers[$poi][] = $placemark;
            }
        }
    }

    // Gérer les sous-calques (Folder)
    if (isset($element->Folder)) {
        foreach ($element->Folder as $folder) {
            extractPlacemarks($folder, $layers);
        }
    }
}

function totalPOIs($layers){
    $totalPlacemarks = 0;
    foreach ($layers as $placemarks) {
        $totalPlacemarks += count($placemarks);
    }
    return $totalPlacemarks;
}

function deduplicate($layers, $threshold=0.0001) {
    global $debug;

    if($debug) echo("Enter deduplicate<br/>");

    // Créer un tableau temporaire pour stocker les calques modifiés
    $newLayers = [];

    foreach ($layers as $icon => $placemarks) {
        $uniquePlacemarks = [];
        $seen = [];

        foreach ($placemarks as $placemark) {
            $name = (string) $placemark->name;
            $styleUrl = (string) $placemark->styleUrl;
            $coordinates = (string) $placemark->Point->coordinates;

            // Normaliser les valeurs
            $name = strtolower(trim($name));
            $styleUrl = strtolower(trim($styleUrl));

            // Extraire les coordonnées
            list($longitude, $latitude) = explode(',', trim($coordinates));

            // Vérifier si les coordonnées sont proches d'un élément déjà vu
            $uniqueKey = json_encode([$name, $styleUrl, $latitude, $longitude]);
            if($debug) echo("$uniqueKey<br/>");

            // Vérifier les doublons avec une marge d'erreur
            $isDuplicate = false;
            foreach ($seen as $existingKey) {
                list($existingName, $existingStyle, $existingLatitude, $existingLongitude) = json_decode($existingKey);
                if($debug) echo("LIST $uniqueKey<br/>");

                // Calculer la distance entre les coordonnées
                if (abs($latitude - $existingLatitude) < $threshold && abs($longitude - $existingLongitude) < $threshold) {
                    $isDuplicate = true;
                    if($debug) echo("Duplicate<br/>");
                    break;
                }
                if($debug) echo("NEXT $uniqueKey<br/>");
            }
            if($debug) echo("EXIT FOR $uniqueKey<br/>");

            // Si pas doublon, on l'ajoute dans le tableau temporaire
            if (!$isDuplicate) {
                $seen[] = $uniqueKey;
                $uniquePlacemarks[] = $placemark;
            }
            if($debug) echo("EXIT $uniqueKey<br/>");
        }

        // Ajouter les placemarks uniques dans le tableau temporaire des layers
        $newLayers[$icon] = $uniquePlacemarks;
        if($debug) echo("Exit layer $icon<br/>");
    }

    // Remplacer les layers par les layers dédupliqués
    if($debug) echo("Exit deduplicate<br/>");
    return $newLayers;

}


if(!empty($_FILES['kmlfile']['tmp_name'])){
    //var_dump($_FILES);exit;
    if(strpos($_FILES['kmlfile']['type'],".kml+xml")===0) exit("File: KML needed");
    $kmlfile=$_FILES['kmlfile']['tmp_name'];
    $kml = simplexml_load_file($kmlfile);
    //dump($kml);exit;

    $singleLayer = isset($_POST['single_layer']) && $_POST['single_layer'] == '1';
    $waterLayer = isset($_POST['water_layer']) && $_POST['water_layer'] == '1';

    // Tableau pour regrouper les POIs par icône
    $layers = [];
    $waterPOIs = [];
    extractPlacemarks($kml->Document, $layers);

    $poi_count = totalPOIs($layers);
    if($debug) echo("POIs $poi_count<br/>");

    // Supprime les doublons
    $layers = deduplicate($layers);
    if($debug) echo("OUT deduplicate");
    $clayers = count($layers);
    $new_poi_count = count($layers);
    if($debug) echo("New POIs $new_poi_count in $clayers layer(s)<br/>");

    // Trier les calques par ordre décroissant de leur nombre de POIs
    uasort($layers, function($a, $b) {
        return count($b) - count($a);
    });

    if($debug) echo("Ready to generate<br/>");

    $newKml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"></kml>');
    $document = $newKml->addChild('Document');

    copyXML($kml->Document->Style, $document);
    copyXML($kml->Document->StyleMap, $document);

    $new_poi_count = 0;
    if ($singleLayer) {
        // Ajouter tous les placemarks dans un seul calque
        $folder = $document->addChild('Folder');
        $folder->addChild('name', 'All POIs');

        foreach ($layers as $icon => $placemarks) {
            if ($waterLayer && $icon == 'drink') {
                $waterPOIs = array_merge($waterPOIs, $placemarks);
            } else {
                copyXML($placemarks, $folder);
            }
        }

        if ($waterLayer && !empty($waterPOIs)) {
            $waterFolder = $folder->addChild('Folder');
            $waterFolder->addChild('name', 'Water POIs');
            copyXML($waterPOIs, $waterFolder);
        }

    } else {
        // Ajouter les Placemarks regroupés par calque
        foreach ($layers as $icon => $placemarks) {
            $folder = $document->addChild('Folder');
            $icons = count($placemarks);
            if($icons >1) $s="s"; else $s="";
            $folder->addChild('name', $icons." ".$icon.$s);

            copyXML($placemarks, $folder);

        }
    }

    // Affichage des statistiques
    if($debug){
        $totalLayers = count($layers);

        // Calcul des statistiques
        $totalPlacemarks = totalPOIs($layers);

        echo "<h2>Statistics KML</h2>";
        echo "<p>Layers: $totalLayers</p>";
        echo "<p>Initial POIs count: $poi_count</p>";
        echo "<p>New POIs count: $totalPlacemarks</p>";
        exit();
    }else{

        // Sauvegarde du nouveau fichier KML
        header('Content-Type: application/vnd.google-earth.kml+xml');
        header('Content-Disposition: attachment; filename="new_kml_file.kml"');
        
        // Sortie du contenu du fichier KML
        echo $newKml->asXML();
    }

}else{

    gpx::htmlHead();

?>
    <h1>KML POIs sorting et deduplicate</h1>
    <form action="" enctype="multipart/form-data" method="post">
    <p>Select a KML file<p>
    <input type="file" name="kmlfile" size="80" style="width:80%"><br/>
    <input type="checkbox" name="single_layer" value="1">
    <label for="single_layer">Generate a single layer</label><br/>
    <input type="checkbox" name="water_layer" value="1"><img src="images/wateri.png" style="width:15px;height:15px"/>
    <label for="water_layer">Place water POIs in a separate layer</label><br/><br/>
    <input type="submit" value="RUN">
    </form>
    <p>Open a new map, import all KML POIs layers, export KML of the map and then use this app.</p>

<?php

    gpx::htmlFooter();
}

?>