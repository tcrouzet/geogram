<?php

namespace App\Utils;

class Tools 
{

    public static function MyDateFormat($timestampInput, $route, $justhour=false){
        if (is_numeric($timestampInput) && (int)$timestampInput == $timestampInput && $timestampInput > 0) {
            $timestamp = $timestampInput;
        } else {
            // Convertir la chaîne de date en timestamp Unix
            $timestamp = strtotime($timestampInput);
        }
    
        if($route["routeunit"]==1){
            //Emperial
            $format="g:ia";
        }else{
            $format="G:i";
        }
    
        if(!$justhour) $format.=" Y/n/j";
        
        return date( $format, self::timezone($timestamp, self::TimeDiff($route)) );    
    }
    
    public static function TimeDiff($route){
        //Heure d'été, décallage heure d'été et heure Paris
        return $route["routetimediff"]-1;
    }
    
    public static function meters_to_distance($meters,$route,$display=1){
    
        if($route["routeunit"]==1){
            $r = number_format(intval($meters*0.621371/1000));
            if($display)
                return $r."mi";
            else
                return $r;
        }else{
            $r = intval($meters/1000);
            if($display)
                return $r."km";
            else
                return $r;
        }
    
    }

    public static function timezone($timestamp,$zone=0){
        return $timestamp-$zone*3600;
    }
    
    public static function photo64decode($photofile){

        // Extraction du type MIME (si présent)
        $matches = [];
        preg_match('/data:image\/(\w+);base64/', $photofile, $matches);
        $imageType = $matches[1] ?? 'jpeg'; // Par défaut, on considère que c'est un JPEG
    
        // Suppression du préfixe
        $base64_string = str_replace('data:image/' . $imageType . ';base64,', '', $photofile);
    
        // Décodage en base64
        $data = base64_decode($base64_string);
    
        // Enregistrement du fichier
        $tmpFilename = tempnam(sys_get_temp_dir(), 'photo_');
    
        if (file_put_contents($tmpFilename, $data) === false) {
            return false;
        }
    
        return $tmpFilename;
    
    }

    public static function resizeImage($sourcefile, $targetfile, $maxSize) {

        $imageInfo = getimagesize($sourcefile);
        if ($imageInfo === false) {
            return false;
        }
    
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
    
        // Calculer le ratio de redimensionnement
        $ratio = min($maxSize / $width, $maxSize / $height);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;
    
        // Créer une nouvelle image avec la taille calculée
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);
    
        // Charger l'image source selon son type
        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcefile);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcefile);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcefile);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($sourcefile);
                break;
            default:
                return false;
        }
    
        if (!$sourceImage) {
            return false;
        }
    
        // Charger l'image d'origine
        $sourceImage = imagecreatefromjpeg($sourcefile);
    
        // Redimensionner l'image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
        // Sauvegarder
        // imagejpeg($newImage, $targetfile, 60);
        imagewebp($newImage, $targetfile, 60);
    
        // Libérer la mémoire
        imagedestroy($newImage);
        imagedestroy($sourceImage);
        unlink($sourcefile);
    
        return true;
    }
    
    public static function slugify($text) {
        // Translitération des caractères spéciaux en équivalents ASCII
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Suppression des caractères non alphanumériques et des espaces
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        // Conversion en minuscules
        $text = strtolower($text);
        // Suppression des tirets en début et fin de chaîne
        $text = trim($text, '-');
        return $text;
    }
    

    // /**
    //  * Formate une date selon les paramètres spécifiés
    //  */
    // public static function formatDate($timestamp, $route = null, $format = null): string 
    // {
    //     if (empty($timestamp)) return '';
        
    //     if (is_string($timestamp)) {
    //         $timestamp = strtotime($timestamp);
    //     }
        
    //     // Si un format spécifique est demandé
    //     if ($format) {
    //         return date($format, $timestamp);
    //     }
        
    //     // Si une route est spécifiée avec des paramètres particuliers
    //     if ($route && isset($route['dateformat'])) {
    //         return date($route['dateformat'], $timestamp);
    //     }
        
    //     // Format par défaut
    //     return date('d.m.y H:i', $timestamp);
    // }
    
    // /**
    //  * Génère les initiales à partir d'un texte
    //  */
    // public static function generateInitials(string $text): string 
    // {
    //     $words = preg_split('/[\s-]+/', $text);
    //     $initials = '';
        
    //     foreach ($words as $word) {
    //         $initials .= mb_strtoupper(mb_substr($word, 0, 1));
    //     }
        
    //     return mb_substr($initials, 0, 2);
    // }
    
    // /**
    //  * Génère une couleur aléatoire sombre
    //  */
    // public static function generateDarkColor(): string 
    // {
    //     $red = mt_rand(0, 128);
    //     $green = mt_rand(0, 128);
    //     $blue = mt_rand(0, 128);
        
    //     return sprintf('#%02X%02X%02X', $red, $green, $blue);
    // }
    
    // /**
    //  * Convertit une distance en format lisible
    //  */
    // public static function formatDistance(float $meters): string 
    // {
    //     if ($meters >= 1000) {
    //         return round($meters / 1000, 1) . ' km';
    //     }
    //     return round($meters) . ' m';
    // }
    
    // /**
    //  * Vérifie si une chaîne est une date valide
    //  */
    // public static function isValidDate(string $date, string $format = 'Y-m-d'): bool 
    // {
    //     $d = \DateTime::createFromFormat($format, $date);
    //     return $d && $d->format($format) === $date;
    // }
    
    // /**
    //  * Tronque un texte à une longueur donnée
    //  */
    // public static function truncate(string $text, int $length = 100, string $ending = '...'): string 
    // {
    //     if (mb_strlen($text) <= $length) {
    //         return $text;
    //     }
    //     return mb_substr($text, 0, $length - mb_strlen($ending)) . $ending;
    // }
    
    // /**
    //  * Nettoie une chaîne HTML
    //  */
    // public static function cleanHTML(string $text): string 
    // {
    //     return htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8');
    // }
}
