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

function deduplicate(&$layers, $threshold=0.0001) {
    global $debug;

    foreach ($layers as &$placemarks) {
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

            // Vérifier les coordonnées avec une marge de 1 mètre
            $uniqueKey = "$name|$styleUrl|$latitude|$longitude";
            if($debug) {
                echo("$uniqueKey</br>");
            }

            // Comparer avec les placemarks déjà vus
            $isDuplicate = false;
            foreach ($seen as $existingKey) {
                list($existingName, $existingStyle, $existingLatitude, $existingLongitude) = explode('|', $existingKey);

                // Calculer la distance entre les coordonnées
                if (abs($latitude - $existingLatitude) < $threshold && abs($longitude - $existingLongitude) < $threshold) {
                    $isDuplicate = true;
                    break;
                }
            }

            // Si ce n'est pas un doublon, on l'ajoute
            if (!$isDuplicate) {
                $seen[] = $uniqueKey;
                $uniquePlacemarks[] = $placemark;
            }
        }

        // Remplacer les placemarks par ceux uniques
        $placemarks = $uniquePlacemarks;
    }
}

if(!empty($_FILES['kmlfile']['tmp_name'])){
    //var_dump($_FILES);exit;
    if(strpos($_FILES['kmlfile']['type'],".kml+xml")===0) exit("File: KML needed");
    $kmlfile=$_FILES['kmlfile']['tmp_name'];
    $kml = simplexml_load_file($kmlfile);
    //dump($kml);exit;

    $singleLayer = isset($_POST['single_layer']) && $_POST['single_layer'] == '1';

    // Tableau pour regrouper les POIs par icône
    $layers = [];
    extractPlacemarks($kml->Document, $layers);

    $poi_count = totalPOIs($layers);

    // Supprime les doublons
    deduplicate($layers);

    $clayers = count($layers);

    // Trier les calques par ordre décroissant de leur nombre de POIs
    uasort($layers, function($a, $b) {
        return count($b) - count($a);
    });

    $newKml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"></kml>');
    $document = $newKml->addChild('Document');

    copyXML($kml->Document->Style, $document);
    copyXML($kml->Document->StyleMap, $document);

    $new_poi_count = 0;
    if ($singleLayer) {
        // Ajouter tous les placemarks dans un seul calque
        $folder = $document->addChild('Folder');
        $folder->addChild('name', 'All POIs');
        foreach ($layers as $placemarks) {
            copyXML($placemarks, $folder);
        }
        $new_poi_count = count($layers);
    } else {
        // Ajouter les Placemarks regroupés par calque
        foreach ($layers as $icon => $placemarks) {
            $folder = $document->addChild('Folder');
            $icons = count($placemarks);
            $new_poi_count += $icons;
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
    <label for="single_layer">Generate a single layer</label><br/><br/>


    <input type="submit" value="RUN">
    </form>

<?php

    gpx::htmlFooter();
}

?>