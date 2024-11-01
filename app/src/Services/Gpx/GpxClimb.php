<?php

namespace App\Services\Gpx;

use Exception;

class GpxClimb{

    private static $points = [];
    private static $distances = [0];
    private static $windowSize = 200; // Fenêtre de lissage en mètres.
    private static $devtotal=0;
    private static $old_elevation=0;
    //private static $i=0;

    public static function devtotal($point){

        if (!is_object($point) || !property_exists($point, 'elevation') || !property_exists($point, 'difference')) {
            throw new Exception('Expected $point to be an gpx object.');
        }

        $lastIndex = count(self::$points) - 1;
        self::$points[] = $point;
        if ($lastIndex >= 0) {
            self::$distances[] = self::$distances[$lastIndex] + $point->difference;
        }else{
            self::$old_elevation = $point->elevation;
            return self::$devtotal;
        }

        // Maintient uniquement les points nécessaires dans la fenêtre.
        while (end(self::$distances) - self::$distances[0] > self::$windowSize && count(self::$points) > 1) {
            array_shift(self::$points);
            array_shift(self::$distances);
        }

        $verticalGain = 0;
        $n=0;
        foreach (self::$points as $p) {
            //lecho($p->elevation);
            if($p->elevation){
                $verticalGain += $p->elevation;
                $n++;
            }
        }
        //Average elevation on windows
        if($n>0)
            $new_elevation = $verticalGain/$n;
        else
            $new_elevation = self::$old_elevation;
        
        if($new_elevation > self::$old_elevation){
            self::$devtotal += $new_elevation - self::$old_elevation;
        }

        // lecho(self::$i, "point",round($point->elevation,2), "total",round(self::$devtotal,2), "new", round($new_elevation,2), "old",round(self::$old_elevation,2));
        // self::$i++;

        self::$old_elevation = $new_elevation;
        return round(self::$devtotal);
    }

    public static function set($dev){
        self::$devtotal = $dev;
        self::$old_elevation = $dev;
        self::$points = [];
        self::$distances = [0];
    }
}
