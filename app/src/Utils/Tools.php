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
    

    /**
     * Formate une date selon les paramètres spécifiés
     */
    public static function formatDate($timestamp, $route = null, $format = null): string 
    {
        if (empty($timestamp)) return '';
        
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        // Si un format spécifique est demandé
        if ($format) {
            return date($format, $timestamp);
        }
        
        // Si une route est spécifiée avec des paramètres particuliers
        if ($route && isset($route['dateformat'])) {
            return date($route['dateformat'], $timestamp);
        }
        
        // Format par défaut
        return date('d.m.y H:i', $timestamp);
    }
    
    /**
     * Génère les initiales à partir d'un texte
     */
    public static function generateInitials(string $text): string 
    {
        $words = preg_split('/[\s-]+/', $text);
        $initials = '';
        
        foreach ($words as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        
        return mb_substr($initials, 0, 2);
    }
    
    /**
     * Génère une couleur aléatoire sombre
     */
    public static function generateDarkColor(): string 
    {
        $red = mt_rand(0, 128);
        $green = mt_rand(0, 128);
        $blue = mt_rand(0, 128);
        
        return sprintf('#%02X%02X%02X', $red, $green, $blue);
    }
    
    /**
     * Convertit une distance en format lisible
     */
    public static function formatDistance(float $meters): string 
    {
        if ($meters >= 1000) {
            return round($meters / 1000, 1) . ' km';
        }
        return round($meters) . ' m';
    }
    
    /**
     * Crée un slug à partir d'un texte
     */
    public static function slugify(string $text): string 
    {
        // Translittération des caractères spéciaux
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Conversion en minuscules
        $text = strtolower($text);
        
        // Remplace les caractères non alphanumériques par des tirets
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        
        // Supprime les tirets multiples
        $text = preg_replace('/-+/', '-', $text);
        
        // Supprime les tirets en début et fin
        return trim($text, '-');
    }
    
    /**
     * Vérifie si une chaîne est une date valide
     */
    public static function isValidDate(string $date, string $format = 'Y-m-d'): bool 
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Tronque un texte à une longueur donnée
     */
    public static function truncate(string $text, int $length = 100, string $ending = '...'): string 
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length - mb_strlen($ending)) . $ending;
    }
    
    /**
     * Nettoie une chaîne HTML
     */
    public static function cleanHTML(string $text): string 
    {
        return htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8');
    }
}
