<?php

namespace App\Utils;

class Tools 
{

    public static function MyDateFormat($timestampInput, $route, $justhour=false){

        return self::timestamp_to_date($timestampInput, $route["routeunit"], $justhour, $route["routetimediff"]);
    }

    public static function timestamp_to_date($timestampInput, $unit=0, $justhour=false, $timediff=0){
        if (is_numeric($timestampInput) && (int)$timestampInput == $timestampInput && $timestampInput > 0) {
            $timestamp = $timestampInput;
        } else {
            $timestamp = strtotime($timestampInput);
        }

        $now = time();
        $diff = $now - $timestamp;
        
        // Si moins de 24 heures, afficher "X h Y min ago"
        if ($diff < 86400 && $diff > 0) { // 86400 secondes = 24 heures
            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);
            
            if ($hours > 0) {
                $minutesText = ($minutes > 0) ? $minutes : "";
                return $hours . "h" . $minutesText;
            } else {
                return $minutes . "m";
            }
        }else{
            $days = floor($diff / 86400);
            return $days . "d";    
        }

        $adjustedTimestamp = self::timezone($timestamp, $timediff);

        if($unit==1){
            //Emperial
            $format="g:ia";
        }else{
            $format="G:i";
        }
    
        if(!$justhour) $format.=" Y/n/j";
        
        return date( $format, $adjustedTimestamp);
    }

    public static function TimeDiff($routetimediff){
        //Heure d'été, décallage heure d'été et heure Paris
        return $routetimediff-1;
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
        return $timestamp+$zone*60;
    }

    public static function photo64decode($photofile){
        
        if (empty($photofile)) {
            lecho("Base64 error: Empty photo file");
            return ['status' => 'error', 'message' => 'Base64 error: Empty photo file'];
        }
        
        // 🚀 Une seule regex pour tout faire d'un coup
        if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $photofile, $matches)) {
            lecho("Base64 error: Invalid data URI format");
            return ['status' => 'error', 'message' => 'Base64 error: Invalid data URI format'];
        }
        
        $imageType = strtolower($matches[1]);
        $base64_string = $matches[2];
        
        // Vérifier le type d'image
        $allowedTypes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        if (!in_array($imageType, $allowedTypes)) {
            lecho("Base64 error: Unsupported image type: " . $imageType);
            return ['status' => 'error', 'message' => 'Base64 error: Unsupported image type: ' . $imageType];
        }
        
        // Décodage base64
        $data = base64_decode($base64_string, true);
        if ($data === false || empty($data)) {
            lecho("Base64 error: Base64 decode failed");
            return ['status' => 'error', 'message' => 'Base64 error: Base64 decode failed'];
        }
        
        $dataSize = strlen($data);
        if ($dataSize < 50) {
            lecho("Base64 error: Image too small: " . $dataSize . " bytes");
            return ['status' => 'error', 'message' => 'Base64 error: Image too small'];
        }
        
        $imageInfo = @getimagesizefromstring($data);
        if ($imageInfo === false) {
            lecho("Base64 error: Invalid image data");
            return ['status' => 'error', 'message' => 'Base64 error: Invalid image data'];
        }
        
        $tempFile = TempFiles::getInstance()->createTempFile('photo_', $data);
        if ($tempFile === false) {
            lecho("Base64 error: Failed to create temporary file");
            return ['status' => 'error', 'message' => 'Base64 error: Failed to create temporary file'];
        }
        
        lecho("Base64 photo decoded successfully: " . $dataSize . " bytes, type: " . $imageType);
        return ['status' => 'success', 'file' => $tempFile, 'type' => $imageType, 'size' => $dataSize];
    }


    public static function resizeImage($sourcefile, $targetfile, $maxSize) {

        $imageInfo = getimagesize($sourcefile);
        if ($imageInfo === false) {
            return false;
        }
    
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];

        $orientation = self::need2rotate($sourcefile);
    
        // Calculer le ratio de redimensionnement
        $ratio = min($maxSize / $width, $maxSize / $height);
        if($ratio>1) $ratio = 1;
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
    
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
        
        // Redimensionner l'image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        if ($orientation){
            $newImage = imagerotate($newImage, $orientation, 0);
        }
    
        // Sauvegarder
        imagewebp($newImage, $targetfile, IMAGE_COMPRESS);
    
        // Libérer la mémoire
        imagedestroy($newImage);
        imagedestroy($sourceImage);
        TempFiles::getInstance()->cleanup();
        return true;
    }

    public static function need2rotate($imagePath) {
        if (!function_exists('exif_read_data')) {
            return false;
        }
        
        $exif = @exif_read_data($imagePath);
        
        if ($exif && isset($exif['Orientation'])) {
            lecho("EXIF Orientation found: " . $exif['Orientation']);
            switch ($exif['Orientation']) {
                case 3: // 180°
                    return 180;
                case 6: // 90° sens horaire
                    return -90;
                case 8: // 90° sens anti-horaire
                    return 90;
                default:
                    return false;
            }
        }
        return false;
    }

    public static function rotateImageFile($imagePath, $orientation=-90) {
        lecho("rotateImageFile $orientation");
        $image = imagecreatefromwebp($imagePath);
        if (!$image) return false;
        
        $rotatedImage = imagerotate($image, $orientation, 0);
        if (!$rotatedImage) {
            imagedestroy($image);
            return false;
        }
        
        $success = imagewebp($rotatedImage, $imagePath, IMAGE_COMPRESS);
        lecho("rotateImageFile $success");
        
        imagedestroy($image);
        imagedestroy($rotatedImage);
        
        return $success;
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
    
    public static function initial($name){
        $r=substr(self::fName($name), 0, 2);
        $r=strtr($r, 'áàâãäåçéèêëìíîïñóòôõöøúùûüýÿ', 'aaaaaaceeeeiiiinoooooouuuuyy');
        return ucfirst($r);
    }

    public static function fName($name){
        return self::format_title($name);
    }
    
    public static function format_title($message){
        $title = iconv('UTF-8', 'ASCII//TRANSLIT', trim($message));
        $title = str_replace(" ","_",$title);
        $title = preg_replace('/[^A-Za-z0-9_\-]/', '', $title);        
        return $title;
    }

    public static function getDarkColorCode($number) {
        $code = intval(substr(strval($number), -4));
        $code = $code % 20;
        //https://colorhunt.co/palettes/dark
        $colors =[
            '#712B75',  //violet
            '#533483',  //violet
            '#726A95',  //violet
            '#C147E9',  //violet
            '#790252',  //Violet 
            '#C74B50',
            '#D49B54',
            '#E94560',
            '#950101',
            '#87431D',
            '#2F58CD',  //jaune
            '#0F3460',  //bleu
            '#3282B8',  //bleu
            '#1597BB',  //bleu
            '#46B5D1',  //bleu
            '#346751',  //vert
            '#519872',  //vert
            '#03C4A1',  //vert
            '#6B728E',  //Gris
            '#7F8487'   //Gris
        ];
        //FF4C29 F10086 trop rouge
        return $colors[$code];
    }

    public static function normalizeName(string $name): string {
        // Convertir en minuscules et diviser en mots

        $words = explode(' ', mb_strtolower(trim( str_replace("_"," ",$name) )));
        
        // Capitaliser chaque mot
        $words = array_map(function($word) {
            return mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }, $words);
        
        // Rejoindre les mots
        return implode(' ', $words);
    }

    public static function formatMessage($message) {
        if(empty($message)) return "";
        
        // Expression régulière pour détecter les URLs
        $pattern = '/(https?:\/\/[^\s<]+[^<.,:;"\')\]\s])/i';
        
        // Remplacer les URLs par une icône avec lien
        $formattedMessage = preg_replace_callback($pattern, function($matches) {
            $url = $matches[0];
            // Icône Font Awesome avec lien
            return sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer"><i class="fas fa-link"></i></a>',
                htmlspecialchars($url)
            );
        }, $message);

        $formattedMessage = str_replace(["\r\n", "\r"], "\n", $formattedMessage);
        $formattedMessage = preg_replace('/\n{3,}/', "\n\n", $formattedMessage);
        $formattedMessage = str_replace("\n", "<br/>", $formattedMessage);
        
        return $formattedMessage;
    }

    public static function isStringNotInteger($var){
        return is_string($var) && (!ctype_digit($var) || strval(intval($var)) !== $var);
    }

    public static function getRequestData($key, $default = '') {
        // Essayer POST d'abord
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        // Essayer JSON si POST vide
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            static $jsonData = null;
            if ($jsonData === null) {
                $rawInput = file_get_contents("php://input");
                $jsonData = $rawInput ? json_decode($rawInput, true) : false;
            }
            if ($jsonData && isset($jsonData[$key])) {
                return $jsonData[$key];
            }
        }
        
        return $default;
    }

}
