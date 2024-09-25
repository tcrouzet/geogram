<?php
$url = 'https://tcrouzet.com/images_tc/2024/09/2024-08-12-072202-Gigean-–-Villeneuve-lès-Maguelone.webp';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);

if ($response === false) {
    echo 'Erreur cURL : ' . curl_error($ch);
} else {
    echo 'Connexion réussie avec cURL.';
}

curl_close($ch);
?>