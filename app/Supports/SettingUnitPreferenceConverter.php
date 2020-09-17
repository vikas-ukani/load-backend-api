<?php

namespace App\Supports;


trait SettingUnitPreferenceConverter
{

    /**
     * convertToMetrics
     * kg => lbs
     * km => miles
     * m => yd
     * km/hr => miles/hr
     * min/km => min/miles
     * min/100m => min/100yd
     *
 
     * # Conversion (Imperial → Metric)
     * 1lbs = 0.454 kg (round off to 1 decimal place)
     * 1miles = 1.61 km (round off to 1 decimal place)
     * 1yd = 0.914 (round off to 1 decimal place)
     * 
     * @return void
     */
    public function convertToMetrics($params)
    {
        //  convert kg to lbs
        

    }


    /**
     * convertToImperial
     *
     * 

     * lbs => kg
     * miles => km
     * yd => m
     * miles/hr => km/hr
     * min/miles => min/km
     * min/ 100 yd => min/ 100 m

     * # Conversion (Metric → Imperial)
     * 1kg = 2.205 lbs (round off to .1 decimal place)
     * 1km = 0.6214 miles (round off to .1 decimal place)
     * 1m = 1.094 yd (round off to .1 decimal place)
     *  

     * 
     * @return void
     */
    public function convertToImperial()
    {
        # code...
    }
}
