<?php

namespace App\Supports;

use Illuminate\Support\Arr;

trait SummaryCalculationTrait
{

    /**
     * totalDurationMinute => Calculate Total Duration From Start Time To End Time From Exercises
     *
     * @param  mixed $trainingLog
     * @return void
     */
    public function totalDurationMinute($trainingLog)
    {
        # Calculate Duration From Exercise
        $totalDurationMinute = 0;

        // $exerciseStartTime = array_filter(collect($trainingLog['exercise'])->pluck('start_time')->all());
        // $exerciseEndTime = array_filter(collect($trainingLog['exercise'])->pluck('end_time')->all());

        // $exerciseStartTime = array_first($exerciseStartTime);
        // $exerciseEndTime = array_first($exerciseEndTime);

        /** get start time and end time from first and last object of exercises */
        $arrFirst = Arr::first($trainingLog['exercise']);
        $arrLast = Arr::last($trainingLog['exercise']);

        /** get time from first and last object */
        $exerciseStartTime = $arrFirst['start_time'];
        $exerciseEndTime = $arrLast['end_time'];

        /** generate date format from start and end time */
        $startWorkout = \Carbon\Carbon::createFromDate($exerciseStartTime);
        $endWorkout = \Carbon\Carbon::createFromDate($exerciseEndTime);

        /** get different between start time and end time */
        $totalDurationInTime = $startWorkout->diff($endWorkout)->format('%H:%I:%S');
        $totalDurationInTime = \Carbon\Carbon::parse($totalDurationInTime);

        /** calculate minute from duration time  */
        $totalDurationMinute = ($totalDurationInTime->hour * 60) + $totalDurationInTime->minute + ($totalDurationInTime->second / 60);

        // dd(__METHOD__ . __LINE__, $totalDurationInTime, $totalDurationMinute, $exerciseStartTime, $exerciseEndTime);
        return $totalDurationMinute;
    }


    public function getTotalDistanceFromStartEndLatitudeLongitude($trainingLog)
    {
        /* get all lat long from exercise object. */
        $start_lat  = array_first(array_filter(collect($trainingLog['exercise'])->pluck(['start_lat'])->all()));
        $start_long = array_first(array_filter(collect($trainingLog['exercise'])->pluck(['start_long'])->all()));
        $end_lat = array_first(array_filter(collect($trainingLog['exercise'])->pluck(['end_lat'])->all()));
        $end_long = array_first(array_filter(collect($trainingLog['exercise'])->pluck(['end_long'])->all()));

        /** Finally Total Distance */
        $total_distance = $this->distance($start_lat, $start_long, $end_lat, $end_long, "K");
        // $response['total_distance_code'] = "A";
        return $total_distance;
    }

    public function distance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        } else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1))
                * sin(deg2rad($lat2))
                + cos(deg2rad($lat1))
                * cos(deg2rad($lat2))
                * cos(deg2rad($theta));

            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);

            if ($unit == "K") {
                return ($miles * 1.609344);
            } else if ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }

    /** Generate Distance For "Cycle" */
    public function findTotalDistanceUsingDuration($exercises)
    {
        $allDuration = 0;
        foreach ($exercises as $key => $exercise) {
            if (isset($exercise['pace'], $exercise['duration'])) {
                /** convert pace to seconds */
                /** 
                 * Step 1 - Convert Pace timing to seconds
                 * Lap 1 Pace timing to seconds → 6 x 60 = 360
                 */
                $paceToSpeedArray = explode(':', $exercise['pace']);
                $paceToSpeed = ($paceToSpeedArray[0] * 60) + $paceToSpeedArray[1];

                /**
                 * Step 2 – Find Speed
                 * Lap 1 Speed → (60 x 60)  360 = 10 km/hr
                 */
                $totalSpeed = round((60 * 60) / $paceToSpeed, 1);

                /**
                 * Step 3 – Find Total Duration
                 * Lap 1 Duration (in hour) → 20  60 = 0.3333 (4 decimals place)
                 * "duration" => "00:20:00"
                 */
                $durationArray = explode(':', $exercise['duration']);
                // $totalDuration = round((($durationArray[1]) / 60), 4);
                $totalDuration = round(
                    (int) $durationArray[0]
                        + ((int) $durationArray[1] / 60)
                        + ((int) $durationArray[2] / 3600),
                    4
                );
                //                     ($durationArray[1] ?? 0) + (($durationArray[1]) / 60) + (($durationArray[2] ?? 0) / 60),
                // dd('minute ', $totalDuration, $durationArray, (int) $durationArray[0], ((int) $durationArray[1] / 60), ((int) $durationArray[2] / 60));
                /**
                 * Step 4 – Find Distance
                 * Lap 1 Distance → 0.3333 x 10 = 3.333 = 3.30km (2 decimals place)
                 */
                $distanceByDurationPace[] = round(($totalDuration * $totalSpeed), 2);

                // dd(
                //     'Check ',
                //     "Total duration = " . $exercise['duration'],
                //     "Total Pace = " . $exercise['pace'],
                //     $paceToSpeed,
                //     $totalSpeed,
                //     $totalDuration,
                //     $distanceByDurationPace
                // );
            }
            if (isset($exercise['speed'], $exercise['duration'])) {
                /**    speed && duration */
                /**
                 * Step 1
                 * Lap 1 Duration in hour → 20 / 60 = 0.3333 (4 decimals place)
                 */
                $durationArray = explode(':', $exercise['duration']);
                // $totalDuration = round((($durationArray[1]) / 60), 4);
                $totalDuration = round(
                    (int) $durationArray[0]
                        + ((int) $durationArray[1] / 60)
                        + ((int) $durationArray[2] / 3600),
                    // + ((int) $durationArray[2] / 60),
                    4
                );
                // dd('total duration', $totalDuration, $durationArray);

                /**
                 * Step 2
                 * Lap 1 Distance → 0.3333 x 9 = 2.9997 = 3km
                 */
                $distanceByDurationSpeed[] = round($totalDuration * ($exercise['speed'] * 1), 4);
            }
        }

        if (isset($distanceByDurationSpeed)) {
            /** SPEED && DURATION
             * Step 3 – Find Total Distance for all Laps
             * Total Distance → 3.3 + 4.2 = 7.5 km (SUM OF ALL STEP 4)
             */
            // dd('SPEED && DURATION ', $distanceByDurationSpeed,    array_sum($distanceByDurationSpeed));
            return array_sum($distanceByDurationSpeed);
        } else if (isset($distanceByDurationPace)) {
            /** PACE && DURATION
             * Step 5 – Find Total Distance for all Laps
             * Total Distance → 3.3 + 4.2 = 7.5 km (SUM OF ALL STEP 4)
             */
            // dd('PACE && DURATION', $distanceByDurationPace,    array_sum($distanceByDurationPace));
            return array_sum($distanceByDurationPace);
        }
    }

    public function calculateDurationCalculationGuid($exercises)
    {
        $findDuration = [];
        foreach ($exercises as $key => $exercise) {
            if (isset($exercise['pace'], $exercise['distance'])) {
                // dd('pace and distance');
                # if pace 
                /** convert pace to seconds */
                /** 
                 * Step 1 - Convert Pace timing to seconds
                 * Lap 1 Pace timing to seconds → 6 x 60 = 360
                 */
                $paceToSpeedArray = explode(':', $exercise['pace']);
                $paceToSpeed = ($paceToSpeedArray[0] * 60) + $paceToSpeedArray[1];

                /**
                 * Step 2 – Find Speed
                 * Lap 1 Speed → (60 x 60)  360 = 10 km/hr
                 */
                $totalSpeed = round((60 * 60) / $paceToSpeed, 1);

                /**
                 * Step 3 – Find Duration
                 * Lap 1 Duration (in min) → (3.5/ 10) x 60 = 21.0
                 */
                $findDuration[] = round((($exercise['distance'] / $totalSpeed) *  60), 4);
                // dd('pace to second', $findDuration,  $totalSpeed, $paceToSpeed,  $exercise);
            } else if (isset($exercise['distance'], $exercise['speed'])) {
                /**
                 * Step 1 – Find Duration
                 * Lap 1 Duration (in min) → (3.5/ 9) x 60 = 23.3333 (4 decimals place)
                 */
                $findDuration[] = round((($exercise['distance'] / $exercise['speed']) * 60), 4);
                // dd('$findDuration', $findDuration, $exercise['distance'], $exercise['speed']);
            }
        }
        $allLapsTotalDuration = array_sum($findDuration);
        return $allLapsTotalDuration;

        /**
         * Step 4 – Find Total Duration
         * Total Duration → 21 + 28.5714 = 49.5714 mins = 49 mins 34 secs
         * (0.5714 x 60 = 34.284 = 34 secs (whole number))
         */


        // dd('all sum together', $allLapsTotalDuration, $findDuration);
    }

    public function calculateAverageSpeedGuide($exercises, $totalDistance, $totalDurationMinute)
    {
        $totalDurationMinutes  = 0;
        $avg_speed = 0;
        foreach ($exercises as $key => $exercise) {
            // dd('asd', $exercise);
            if (isset($exercise['speed'], $exercise['duration'])) {
                $durationArray = explode(":", $exercise['duration']);
                $totalDurationMinutes += (
                    ((int) $durationArray[0] * 60)  // hour to  minutes
                    + ((int) $durationArray[1]) // minutes
                    + ((int) $durationArray[2] / 60) // second to minutes
                );
            } else if (isset($exercise['duration'], $exercise['pace'])) {
                # pace calculation
                /** 
                 * Step 1 - Convert Pace timing to seconds
                 * Refer to Distance Calculation Guide.
                 * Lap 1 Pace timing to seconds → 6 x 60 = 360
                 */
                $paceToSpeedArray = explode(':', $exercise['pace']);
                $paceToSpeed = ($paceToSpeedArray[0] * 60) + $paceToSpeedArray[1];

                /**
                 * Step 2 – Find Speed
                 * Refer to Distance Calculation Guide.
                 * Lap 1 Speed → (60 x 60)  360 = 10 km/hr
                 */
                $totalSpeed = round((60 * 60) / $paceToSpeed, 1);
                $durationArray = explode(":", $exercise['duration']);
                // dd('aof', $durationArray, $exercise);

                $totalDurationMinutes += (
                    ((int) $durationArray[0] * 60)  // hour to  minutes
                    + ((int) $durationArray[1]) // minutes
                    + ((int) $durationArray[2] / 60) // second to minutes
                );
            } else {
                $totalDurationMinutes = $totalDurationMinute;
            }
        }
        /** step 1 ans */
        $totalDurationHour = round(($totalDurationMinutes / 60), 4);
        // dd('totalDurationHour', $totalDistance, $totalDurationHour, $totalDurationMinutes, $totalDurationMinute, $exercises);
        /**
         * Step 3 and 5 (Common) – Find Average Speed
         * Average Speed → 8/ 0.8333 = 9.6 km/hr (1 decimal place)
         */
        if (isset($totalDurationHour) && $totalDurationHour != 0)
            $avg_speed = round((round($totalDistance, 1) / $totalDurationHour), 1);

        // dd('check avg_speed', "totalDistance = " . $totalDistance, "totalDurationHour " . $totalDurationHour, "totalDurationMinutes " . $totalDurationMinutes, "avg_speed " . $avg_speed);
        return $avg_speed;
    }



    /**
     * calculatePaceCalculationGuid => Average Pace Calculation Guide
     *
     * @param  mixed $exercises
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculatePaceCalculationGuid($exercises, $totalDistance, $totalDurationMinute)
    {
        $totalDurationMinutes = 0;
        /**
         * Step 1 – Find Total Duration
         * 20 + 30 = 50 minutes
         */
        foreach ($exercises as $key => $exercise) {
            if (isset($exercise['duration'])) {
                $durationArray = explode(":", $exercise['duration']);
                $totalDurationMinutes += (
                    ((int) $durationArray[0] * 60)  // hour to  minutes
                    + ((int) $durationArray[1]) // minutes
                    + ((int) $durationArray[2] / 60) // second to minutes
                );
            }
        }
        // return ($totalDurationMinutes) / round($totalDistance, 1);
        return ($totalDurationMinutes == 0 ? $totalDurationMinute : $totalDurationMinutes) / round($totalDistance, 1);
    }

    /**
     * addAllDurationTimeFromExercise => Add it all duration together and return Minutes
     *
     * @param  mixed $exercises
     * @return void
     */
    public function addAllDurationTimeFromExercise($exercises)
    {
        $totalDurationMinutes = 0;
        foreach ($exercises as $key => $exercise) {
            $durationArray = explode(":", $exercise['duration']);
            $totalDurationMinutes += (
                ((int) $durationArray[0] * 60)  // hour to  minutes
                + ((int) $durationArray[1]) // minutes
                + ((int) $durationArray[2] / 60) // second to minutes
            );
        }
        // dd('Total Duration Minutes',  $totalDurationMinutes);
        return $totalDurationMinutes;
        // return (gmdate("H:i:s", (($totalDurationMinutes ?? 0)  * 60))); // convert time format 
    }
}
