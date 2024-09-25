<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Récupère l'URL de l'image WebP à convertir depuis la requête GET
$webp_url = $_GET['url'];

// Vérifie si l'URL a été fournie
if (isset($webp_url)) {
    // Vérifie si l'extension du fichier est .webp
    if (pathinfo($webp_url, PATHINFO_EXTENSION) === 'webp') {

        // Initialisation de cURL
        $ch = curl_init($webp_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $imageData = curl_exec($ch);

        if ($imageData === false) {
            die('Erreur cURL : ' . curl_error($ch));
        }

        curl_close($ch);

        $webp_image = imagecreatefromstring($imageData);
        
        // Crée une nouvelle image en JPEG
        $jpeg_image = imagecreatetruecolor(imagesx($webp_image), imagesy($webp_image));
        
        // Copie l'image WebP dans l'image JPEG
        imagecopy($jpeg_image, $webp_image, 0, 0, 0, 0, imagesx($webp_image), imagesy($webp_image));
        
        // Libère la mémoire
        imagedestroy($webp_image);
        
        // Définit la qualité du JPEG à 50%
        $quality = 50;
        
        // Indique que le contenu est une image JPEG
        header('Content-Type: image/jpeg');
        header('Cache-Control: max-age=3600');
        
        // Affiche l'image JPEG avec une qualité de 50%
        imagejpeg($jpeg_image, null, $quality);
        
        // Libère la mémoire
        imagedestroy($jpeg_image);
    } else {
        // Si l'URL ne pointe pas vers une image WebP, renvoie une erreur 400
        http_response_code(400);
        echo "L'URL spécifiée ne pointe pas vers une image WebP valide.";
    }
} else {
    // Si aucune URL n'est fournie, renvoie une erreur 400
    http_response_code(400);
    echo "Veuillez spécifier une URL d'image WebP à convertir en paramètre 'url'.";
}
?>
