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

        /** get start time and end time from first and last object of exercises */
        $arrFirst = Arr::first($trainingLog['exercise']);
        $arrLast = Arr::last($trainingLog['exercise']);

        // if start_time is "0" means COMPLETE button Clicked
        if ($arrFirst['start_time'] == "") {
            return 0; // COMPLETE button clicked.
        }

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

        /** return it to time */
        return $totalDurationMinute;
    }

    /**
     * getTotalDistanceFromStartEndLatitudeLongitude => get Distance by start of the lat-long and end of the lat-long
     *
     * @param  mixed $trainingLog
     * @return void
     */
    public function getTotalDistanceFromStartEndLatitudeLongitude($trainingLog)
    {
        /** get starting lat long */
        $start_lat  = Arr::first(array_filter(collect($trainingLog['exercise'])->whereNotIn('start_lat', [null, 0, '0'])->pluck(['start_lat'])->all()));
        $start_long = Arr::first(array_filter(collect($trainingLog['exercise'])->whereNotIn('start_lat', [null, 0, '0'])->pluck(['start_long'])->all()));
        // $start_lat  = Arr::first(array_filter(collect($trainingLog['exercise'])->pluck(['start_lat'])->all()));

        $start_long = Arr::first(array_filter(collect($trainingLog['exercise'])->pluck(['start_long'])->all()));

        /** get ending lat long */
        $end_lat = Arr::first(array_filter(collect($trainingLog['exercise'])->whereNotIn('end_lat', [null, 0, '0'])->pluck(['end_lat'])->all()));
        $end_long = Arr::first(array_filter(collect($trainingLog['exercise'])->whereNotIn('end_long', [null, 0, '0'])->pluck(['end_long'])->all()));
        // $end_lat = Arr::first(array_filter(collect($trainingLog['exercise'])->pluck(['end_lat'])->all()));
        // $end_long = Arr::first(array_filter(collect($trainingLog['exercise'])->pluck(['end_long'])->all()));

        /** get a distance between starting and ending lat long */
        if (isset($start_lat, $start_long, $end_lat, $end_long)) {
            $total_distance = $this->distance($start_lat, $start_long, $end_lat, $end_long, "K");
        } else {
            return 0;
        }

        return $total_distance;
    }

    /**
     * distance => Calculating distance by using start of the lat long and end of the lat long.
     *
     * @param  mixed $lat1
     * @param  mixed $lon1
     * @param  mixed $lat2
     * @param  mixed $lon2
     * @param  mixed $unit must be K (km) | M (miles).
     * @return void
     */
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
            } else if ($unit == "M") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }

    /** Generate Distance For "Cycle" */
    public function findTotalDistanceUsingDuration($exercises)
    {
        // NEW
        $isDuration = isset($exercises[0]['duration']);
        $isPace = isset($exercises[0]['pace']);

        $addDistanceArr = [];
        $addDurationArr = [];

        if ($isDuration && !$isPace) {
            # Method 1: To find Total Distance
            # If user uses Duration and Speed:

            foreach ($exercises as $key => $log) {
                # Step 1 – Find lap Duration (in hrs) – Duration should Include ‘Rest’ (if available)
                # Duration = ((Duration in hrs x 60) + (Duration in mins) + (Duration in secs / 60)) / 60
                # Duration (Lap 1) = ((0 x 60) + (20) + (0 / 60)) / 60
                $durationArr = explode(':', $log['duration']);
                $Duration = (($durationArr[0] * 60) + $durationArr[1] + ($durationArr[2] / 60));

                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $Duration = $Duration + ($restArr[0]  + $restArr[1] / 60);
                }
                $Duration = $Duration / 60;


                # Step 2 – Find lap Distance
                # Distance = Duration x Speed
                # Distance (Lap 1) = 0.3333 x 9 = 2.9997 = 3 km
                $addDistanceArr[] = $Duration  * $log['speed'];
            }

            # Step 3 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            $TotalDistance = round((array_sum($addDistanceArr)), 1);
            return $TotalDistance;
        } else if ($isDuration && $isPace) {
            # Method 2: To find Total Distance
            # If user uses Duration and Pace:

            foreach ($exercises as $key => $log) {
                # Step 1 - Convert Pace to seconds
                # Pace to seconds = (mins x 60) + secs
                # Pace to seconds (Lap 1) = (6 x 60) + 0 = 360
                if (isset($log['pace'])) {
                    $paceArr = explode(':', $log['pace']);
                    $PaceToSeconds = ($paceArr[0] * 60) + $paceArr[1];

                    # Step 2 – Find lap Speed
                    # Speed = 3600 / Pace in seconds
                    # Speed (Lap 1) = 3600 / 360 = 10.0 km/hr (1 decimal place)
                    $Speed = round((3600 / $PaceToSeconds), 1);

                    # Step 3 – Find lap Duration (in hrs) – Duration should Include ‘Rest’ (if available)
                    # Duration = ((Duration in hrs x 60) + (Duration in mins) + (Duration in secs / 60)) / 60
                    # Duration (Lap 1) = ((0 x 60) + (20) + (0 / 60)) / 60
                    # = (0 + 20 + 0) / 60
                    # = 0.3333 (4 decimals place)
                    $durationArr = explode(':', $log['duration']);
                    $duration = ($durationArr[0] * 60) + $durationArr[1] + ($durationArr[2] / 60);
                    if (isset($log['rest'])) {
                        $restArr = explode(':', $log['rest']);
                        $rest = $restArr[0] + ($restArr[1] / 60);
                        $duration = $duration + $rest;
                    }
                    $Duration = round(($duration / 60), 4);


                    # Step 4 – Find lap Distance
                    # Distance = Duration x Speed
                    # Distance (Lap 1) = 0.3333 x 10 = 3.333 = 3.30 km (2 decimals place)
                    $addDistanceArr[] =  round(($Duration * $Speed), 1);
                }
            }

            # Step 5 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 3.30 + 4.20 = 7.50 = 7.5 km (1 decimal place)
            $TotalDistance = round((array_sum($addDistanceArr)), 1);
            return $TotalDistance;
        }

        // OLD
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
                 * Lap 1 Speed → (60 x 60) / 360 = 10 km/hr
                 */
                $totalSpeed = round((60 * 60) / $paceToSpeed, 1);

                /**
                 * Step 3 – Find Total Duration
                 * Lap 1 Duration (in hour) → 20 / 60 = 0.3333 (4 decimals place)
                 * "duration" => "00:20:00"
                 */
                $durationArray = explode(':', $exercise['duration']);
                $totalDuration = round(
                    (int) $durationArray[0]
                        + ((int) $durationArray[1] / 60)
                        + ((int) $durationArray[2] / 3600),
                    4
                );
                /**
                 * Step 4 – Find Distance
                 * Lap 1 Distance → 0.3333 x 10 = 3.333 = 3.30km (2 decimals place)
                 */
                $distanceByDurationPace[] = round(($totalDuration * $totalSpeed), 2);
            }
            if (isset($exercise['speed'], $exercise['duration'])) {
                /**    speed && duration */
                /**
                 * Step 1
                 * Lap 1 Duration in hour → 20 / 60 = 0.3333 (4 decimals place)
                 */
                // $durationArray = explode(':', $exercise['duration']);
                // $totalDuration = round((($durationArray[1]) / 60), 4);
                $totalDuration = $this->convertDurationToHours($exercise['duration']);

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
            return array_sum($distanceByDurationSpeed);
        } else if (isset($distanceByDurationPace)) {
            /** PACE && DURATION
             * Step 5 – Find Total Distance for all Laps
             * Total Distance → 3.3 + 4.2 = 7.5 km (SUM OF ALL STEP 4)
             */

            return array_sum($distanceByDurationPace);
        }
    }

    /**
     * calculateDurationCalculationGuid => return duration in minutes only
     *
     * @param  mixed $exercises
     * @return void
     */
    public function calculateDurationCalculationGuid($exercises)
    {
        // NEW
        $isDuration = isset($exercises[0]['duration']);
        $isPace = isset($exercises[0]['pace']);

        $addDistanceArr = [];
        $addDurationArr = [];
        $addRest = [];

        if (!$isDuration && !$isPace) {
            # Method 1: To find Total Duration
            # If user uses Distance and Speed:
            foreach ($exercises as $key => $log) {

                # Step 1 – Find lap Duration (in hrs)
                # Duration = Distance / Speed
                # Duration (Lap 1) = 3.5 / 9 = 0.3889 (4 decimals place)
                $Duration = round(($log['distance'] / $log['speed']), 4);
                $addDurationArr[] = $Duration;

                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $addRest[] = (($restArr[0] / 60) + ($restArr[1])  / 3600);
                }
            }

            # Step 2 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hrs) + (Total Duration in mins / 60) + (Total Duration in secs / 3600)
            $TotalDuration = (array_sum($addDurationArr)  + array_sum($addRest));

            # Step 3 – Convert fraction to time
            # Convert fraction to time (mins) = (0.7367 – 0) x 60
            # = 0.7367 x 60  = 44.202 mins
            $TotalDurationMinute = $TotalDuration * 60;
            return $TotalDurationMinute;
        } else if (!$isDuration &&  $isPace) {
            # Method 2: To find Total Duration
            # If user uses Distance and Pace:

            foreach ($exercises as $key => $log) {
                # Step 1 - Convert Pace to minutes (fraction)
                # Pace to seconds = mins + (secs  60)
                # Pace to seconds (Lap 1) = 6 + (0) = 6
                # Pace to seconds (Lap 2) = 7 + (10  60) = 7.1667
                $paceArr = explode(':', $log['pace']);
                $PaceToSeconds = ($paceArr[0]) + ($paceArr[1] * 60);

                # Step 2 – Find lap Speed
                # Speed = 60  Step 1
                # Speed (Lap 1) = 60  6 = 10.00 km/hr (2 decimals place)
                # Speed (Lap 2) = 60  7.1667 = 8.37 km/hr (2 decimals place)
                $Speed = round((60 / $PaceToSeconds), 2);

                # Step 3 – Find lap Duration (in min fraction)
                # Duration = (Distance  Speed) x 60
                # Duration (Lap 1) = (3.5  10) x 60 = 21.0000 (4 decimals place)
                # Duration (Lap 2) = (4.0  8.37) x 60 = 28.6738 (4 decimals place)
                $Duration = round((($log['distance'] * $Speed) * 60), 4);

                $addDurationArr[] = $Duration;
                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $addRest[] = ($restArr[0]) + ($restArr[1] / 60);
                }
            }

            # Step 4 – Convert min fraction to time
            # Convert fraction to time (Lap 2) = 0.6738 x 60
            # = 40.428 = 40 secs (whole number) 
            $TotalDurationHour = round((array_sum($addDurationArr) + array_sum($addRest)), 4);

            $TotalDurationMinutes  = $TotalDurationHour * 60;
            return $TotalDurationMinutes;
        }

        $findDuration = [];
        foreach ($exercises as $key => $exercise) {
            if (isset($exercise['pace'], $exercise['distance'])) {
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
                 * Lap 1 Speed → (60 x 60) / 360 = 10 km/hr
                 */
                $totalSpeed = round((60 * 60) / $paceToSpeed, 1);

                /**
                 * Step 3 – Find Duration
                 * Lap 1 Duration (in min) → (3.5/ 10) x 60 = 21.0
                 */
                $findDuration[] = round((($exercise['distance'] / $totalSpeed) *  60), 4);
            } else if (isset($exercise['distance'], $exercise['speed'])) {
                /**
                 * Step 1 – Find Duration
                 * Lap 1 Duration (in min) → (3.5/ 9) x 60 = 23.3333 (4 decimals place)
                 */
                $findDuration[] = round((($exercise['distance'] / $exercise['speed']) * 60), 4);
            }
        }
        $allLapsTotalDuration = array_sum($findDuration);
        return $allLapsTotalDuration;

        /**
         * Step 4 – Find Total Duration
         * Total Duration → 21 + 28.5714 = 49.5714 mins = 49 mins 34 secs
         * (0.5714 x 60 = 34.284 = 34 secs (whole number))
         */
    }

    /**
     * calculateAverageSpeed_OTHER => Other activity only
     *
     * @param  mixed $exercises
     * @return void
     */
    public function calculateAverageSpeedGuide_OTHER($exercises)
    {
        ## NEW
        $isDuration = isset($exercises[0]['duration']);
        $isPace = isset($exercises[0]['pace']);

        $allDistanceArr = [];
        $allDurationArr = [];
        $addAllRest = [];

        if ($isDuration && !$isPace) {
            # Method 1: To find Average Speed
            # If user uses Duration and Speed:
            foreach ($exercises as $key => $log) {
                # Step 1 – Find lap Distance
                # Distance = Speed x ((Duration in hr) + (Duration in mins / 60) + (Duration in secs / 3600))
                $durationArr = explode(':', $log['duration']);
                $duration = (($durationArr[0] * 60) + ($durationArr[1]) + $durationArr[2] / 60);
                $Distance =  $log['speed'] * ((($durationArr[0]) + ($durationArr[1] / 60) + $durationArr[2] / 3600));
                $allDistanceArr[] = $Distance;

                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $rest = $restArr[0] + ($restArr[1] / 60);
                    $addAllRest[] = $rest;
                }

                $addDurationMinutes[] = $duration;
            }

            # Step 2 – Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 3 + 5 = 8.0 km (1 decimal place)
            $TotalDistance = round(array_sum($allDistanceArr), 1);

            # Step 3 – Find Total Duration (in hr) – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hrs) + (Total Duration in mins / 60) + (Total Duration in secs / 3600)
            $TotalDuration = round(((array_sum($addDurationMinutes) / 60) + count($addAllRest) > 1 ? array_sum($addAllRest) : 0), 4);

            # Step 4 – Find Average Speed
            # Average Speed = Total Distance / Total Duration
            # Average Speed = 8 / 0.8333 = 9.6004 = 9.6 km/hr
            if (in_array($TotalDuration, [0, 0.0])) {
                return 0;
            }
            $AverageSpeed =  $TotalDistance / $TotalDuration;
            return $AverageSpeed;
        } else if (!$isDuration && !$isPace) {
            # Method 3: To find Average Speed
            # If user uses Distance and Speed:

            foreach ($exercises as $key => $log) {
                # Step 1 – Find lap Duration (in hr)
                # Duration = Distance / Speed
                # Duration (Lap 1) = 3.5 / 9 = 0.3888
                $Duration =  round(($log['distance'] / $log['speed']), 4);

                # Step 2 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
                # Total Duration = (Total Duration in hrs) + (Total Duration in mins / 60) + (Total Duration in secs / 3600)
                $rest = 0;
                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $rest =  ($restArr[0] /  60) + $restArr[1] / 3600;
                }
                $allDurationArr[] = $Duration + $rest;

                # Step 3 – Find Total Distance
                # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
                # Total Distance = 3.5 + 4 = 7.5 km
                $allDistanceArr[]  = $log['distance'];
            }

            # Step 4 – Find Average Speed
            # Average Speed = Total Distance / Total Duration
            # Average Speed = 7.5 / 0.7366 = 10.1819 = 10.2 km/hr (1 decimal place)
            $TotalDuration = round(array_sum($allDurationArr), 4);
            $totalDistance = round(array_sum($allDistanceArr), 1);

            if (in_array($TotalDuration, [0, 0.0])) {
                return 0;
            }
            $AverageSpeed = round(($totalDistance / $TotalDuration), 1);
            return $AverageSpeed;
        }
    }

    /**
     * calculateAverageSpeedGuide => calculate avg_speed 
     *
     * @param  mixed $exercises
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculateAverageSpeedGuide($exercises, $totalDistance, $totalDurationMinute)
    {
        ## NEW
        $isDuration = isset($exercises[0]['duration']);
        $isPace = isset($exercises[0]['pace']);

        $allDistanceArr = [];
        $allDurationArr = [];

        $addDurationMinutes = [];
        $addAllRest = [];

        if ($isDuration && !$isPace) {
            # Method 1: To find Average Speed
            # If user uses Duration and Speed:
            foreach ($exercises as $key => $log) {
                # Step 1 – Find lap Distance
                # Distance = Speed x ((Duration in hr) + (Duration in mins / 60) + (Duration in secs / 3600))
                # Distance (Lap 1) = 9 x ((0) + (20 / 60) + (0 / 3600))
                $durationArr = explode(':', $log['duration']);
                $duration = (($durationArr[0]) + ($durationArr[1] / 60) + $durationArr[2] / 3600);
                // $duration = (($durationArr[0] * 60) + ($durationArr[1]) + $durationArr[2] / 60);
                $Distance = round(($log['speed'] * $duration), 1);
                // $Distance =  $log['speed'] * ((($durationArr[0]) + ($durationArr[1] / 60) + $durationArr[2] / 3600));
                $allDistanceArr[] = $Distance;

                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $rest = ($restArr[0] / 60) + ($restArr[1] / 3600);
                    $addAllRest[] = $rest;
                }

                $addDurationMinutes[] = $duration;
            }

            # Step 2 – Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 3 + 5 = 8.0 km (1 decimal place)
            $TotalDistance = round(array_sum($allDistanceArr), 1);

            # Step 3 – Find Total Duration (in hr) – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hrs) + (Total Duration in mins / 60) + (Total Duration in secs / 3600)
            $TotalDuration = round((array_sum($addDurationMinutes)), 4);

            # Step 4 – Find Average Speed
            # Average Speed = Total Distance / Total Duration
            # Average Speed = 8 / 0.8333 = 9.6004 = 9.6 km/hr
            if (in_array($TotalDuration, [0, 0.0])) {
                return 0;
            }

            $AverageSpeed = round(($TotalDistance / $TotalDuration), 1);
            $AverageSpeed = $AverageSpeed == 0 ? 1 : $AverageSpeed;

            #  Step 5 – Find Average Pace
            # Average Pace = 60 / Average Speed
            # Average Pace = 60 / 9.6
            # = 6.25 mins
            $AveragePace = 60 / $AverageSpeed;
            return  $AverageSpeed;
        } else if ($isDuration && $isPace) {
            # Method 2: To find Average Speed
            # If user uses Duration and Pace:

            foreach ($exercises as $key => $log) {
                # Step 1 – Convert Pace to seconds
                # Pace to seconds = (mins x 60) + secs
                $paceArr  = explode(':', $log['pace']);
                $PaceToSecond = ($paceArr[0] * 60) + $paceArr[1];

                # Step 2 – Find lap Speed
                # Speed = 3600 / Pace in seconds
                # Speed (Lap 1) = 3600 / 360 = 10.0 km/hr (1 decimal place)
                $Speed = round((3600 / $PaceToSecond), 1);

                # Step 3 – Find lap Duration (in hr)
                # Duration = (Duration in hrs) + (Duration in mins / 60) + (Duration in secs / 3600)
                # Duration (Lap 1) = (0) + (20 / 60) + (0 / 3600) = 0.3333 (4 decimals place)
                $durationArr = explode(':', $log['duration']);
                $durationInHour = $durationArr[0] + ($durationArr[1] / 60) + ($durationArr[2] / 3600);
                $Duration = round($durationInHour, 4);
                $allDurationArr[] = round($durationInHour, 4);

                # Step 4 – Find lap Distance
                # Distance = Duration x Speed
                # Distance (Lap 1) = 0.3333 x 10 = 3.333 = 3.30 km (2 decimals place)
                $allDistanceArr[] = round(($Duration * $Speed), 2);

                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $addAllRest[] = ($restArr[0] / 60) + ($restArr[1] / 3600);
                }
            }

            # Step 5 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 3.30 + 4.20 = 7.50 = 7.5 km (1 decimal place)
            // $TotalDistance = collect($exercises)->sum('distance');
            $TotalDistance = round(array_sum($allDistanceArr), 1);

            # Step 6 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hrs) + (Total Duration in mins / 60) + (Total Duration in secs / 3600)
            # Total Duration = (0.3333 + 0.5000) + (0 / 60) + (0 / 3600)
            $TotalDuration = round(((array_sum($allDurationArr)) + (count($addAllRest) > 1 ? array_sum($addAllRest) : 0)), 4);

            # Step 7 – Find Average Speed
            # Average Speed = Total Distance / Total Duration
            # Average Speed = 7.5 / 0.8333 = 9.0004 = 9.0 km/hr
            if (in_array($TotalDuration, [0, 0.0])) {
                return 0;
            }
            $AverageSpeed = round(($TotalDistance / $TotalDuration), 1);
            return $AverageSpeed;

            #  Step 8 – Find Average Pace
            # Average Pace = 60 / Average Speed
            # Average Pace = 60 / 9.0
            # = 6.67 mins
            $AveragePace = 60 / $AverageSpeed;
        } else if (!$isDuration &&  !$isPace) {
            # Method 3: To find Average Speed
            # If user uses Distance and Speed:

            foreach ($exercises as $key => $log) {
                # Step 1 – Find lap Duration (in hr)
                # Duration = Distance / Speed
                # Duration (Lap 1) = 3.5 / 9 = 0.3888
                $Duration =  round(($log['distance'] / $log['speed']), 4);

                # Step 2 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
                # Total Duration = (Total Duration in hrs) + (Total Duration in mins / 60) + (Total Duration in secs / 3600)
                $rest = 0;
                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $rest = ($restArr[0] /  60) + $restArr[1] / 3600;
                }
                $allDurationArr[] = $Duration + $rest;

                # Step 3 – Find Total Distance
                # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
                # Total Distance = 3.5 + 4 = 7.5 km
                $allDistanceArr[]  = $log['distance'];
            }
            # Step 4 – Find Average Speed
            # Average Speed = Total Distance / Total Duration
            # Average Speed = 7.5 / 0.7366 = 10.1819 = 10.2 km/hr (1 decimal place)
            $TotalDuration = round(array_sum($allDurationArr), 4);
            $totalDistance = round(array_sum($allDistanceArr), 1);

            if (in_array($TotalDuration, [0, 0.0])) {
                return 0;
            }
            $AverageSpeed = round(($totalDistance / $TotalDuration), 1);
            return $AverageSpeed;

            # Step 5 – Find Average Pace
            # Average Pace = 60 / Average Speed
            # Average Pace = 60 / 10.2
            $AveragePace = 60 / $AverageSpeed;
        } else if (!$isDuration && $isPace) {
            # Method 4: To find Average Speed
            # If user uses Distance and Pace:

            foreach ($exercises as $key => $log) {
                # Step 1 – Convert Pace to seconds
                # Pace to seconds = (mins x 60) + secs
                # Pace to seconds (Lap 1) = (6 x 60) + 0 = 360
                $paceArr = explode(':', $log['pace']);
                $PaceToSeconds = ($paceArr[0] * 60) + $paceArr[1];

                # Step 2 – Find lap Speed
                # Speed = 3600 / Pace in seconds
                # Speed (Lap 1) = 3600 / 360 = 10.0 km/hr (1 decimal place)   
                $Speed = round(3600 / $PaceToSeconds, 1);

                # Step 3 – Find lap Duration (in hr)
                # Duration = Distance / Speed
                # Duration (Lap 1) = 3.5 / 10 = 0.35
                $allDurationArr[] = round($log['distance'] / $Speed, 1);
                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $addAllRest[] = ($restArr[0] / 60)  + ($restArr[1] / 3600);
                }
            }

            # Step 4: Find Total Duration – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hrs) + (Total Duration in mins / 60) + (Total Duration in secs / 3600)
            # Total Duration = (0.35 + 0.48) + (0 / 60) + (0 / 3600)
            $duration = array_sum($allDurationArr);
            $rest = count($addAllRest) > 1 ? array_sum($addAllRest) : 0;
            $TotalDuration = round(($duration + $rest), 4);

            # Step 5 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 3.5 + 4 = 7.5 km
            $TotalDistance = collect($exercises)->sum('distance');

            # Step 6 – Find Average Speed
            # Average Speed = Total Distance / Total Duration
            # Average Speed = 7.5 / 0.83 = 9.0361 = 9.0 km/hr

            if (in_array($TotalDuration, [0, 0.0])) {
                return 0;
            }
            $AverageSpeed = round(($TotalDistance / $TotalDuration), 1);
            return $AverageSpeed;

            # Step 7 – Find Average Pace
            # Average Pace = 60 / Average Speed
            # Average Pace = 60 / 9.0
            $AveragePace = 60 / $AverageSpeed;
        }
    }

    /**
     * calculatePaceCalculationGuid => Average Pace Calculation Guide UPDATED
     *
     * @param  mixed $exercises
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculatePaceCalculationGuid($exercises, $totalDistance, $totalDurationMinute)
    {
        $isDuration = isset($exercises[0]['duration']);
        $isPace = isset($exercises[0]['pace']);

        $allDistanceArr = [];
        $allDurationArr = [];
        # Method 1: To find Average Pace
        # If user uses Duration and Speed:
        if ($isDuration && !$isPace) {

            foreach ($exercises as $key => $log) {
                # Step 1 – Find lap Distance
                # Distance = (((Duration in hrs x 60) + (Duration in mins) + (Duration in secs / 60)) / 60) x Speed
                $DurationArr = explode(':', $log['duration']);
                $addedDuration = ((($DurationArr[0] * 60) + $DurationArr[1] + ($DurationArr[2] / 60)));
                $allDistanceArr[] = round((($addedDuration / 60) * $log['speed']), 2);
                $allDurationArr[] = $addedDuration;
            }

            # Step 2 – Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 3.00 + 5.00 = 8.0 km (1 decimal place)
            $TotalDistance = round(array_sum($allDistanceArr), 1);

            # Step 3 – Find Total Duration (in mins) – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hrs x 60) + (Total Duration in mins) + (Total Duration in secs / 60)
            $TotalDuration = round(array_sum($allDurationArr), 4);

            # Step 4 – Find Average Pace
            # Average Pace = Total Duration / Total Distance
            $AveragePace = round($TotalDuration / $totalDistance, 2);

            return $AveragePace;
        } elseif ($isDuration && $isPace) {
            # Method 2: To find Average Pace
            # If user uses Duration and Pace:

            foreach ($exercises as $key => $log) {
                # Step 1 - Convert Pace to seconds
                # Pace to seconds = (mins x 60) + secs
                $paceArr = explode(':', $log['pace']);
                $PaceToSeconds = ($paceArr[0]  * 60) + $paceArr[1];

                # Step 2 – Find lap Speed
                # Speed = 3600 / Pace in seconds
                # Speed (Lap 1) = 3600 / 360 = 10.00 km/hr (2 decimals place)
                $Speed = round((3600 / $PaceToSeconds), 2);

                # Step 3 – Find lap Distance
                # Distance = (((Duration in hrs x 60) + (Duration in mins) + (Duration in secs / 60)) / 60) x Speed
                # Distance (Lap 1) = (((0 x 60) + (20) + (0 / 60)) / 60) x 10
                $DurationArr = explode(':', $log['duration']);
                $addedDuration = round((($DurationArr[0] * 60) + ($DurationArr[1]) + ($DurationArr[1] / 60)), 4);
                $Distance = round((($addedDuration / 60) * $Speed), 2);
                $allDistanceArr[] = $Distance;

                /** add duration include rest */
                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $addedDuration = $addedDuration + ($restArr[0]  + ($restArr[1] / 60));
                }
                $allDurationArr[] = $addedDuration;
            }

            # Step 4 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 3.33 + 4.19 = 7.52 = 7.5 km (1 decimal place)
            $TotalDistance = round(array_sum($allDistanceArr), 1);

            # Step 5 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hrs x 60) + (Total Duration in mins) + (Total Duration in secs / 60)
            $TotalDuration = round(array_sum($allDurationArr), 1);

            # Step 6 – Find Average Pace
            # Average Pace = Total Duration / Total Distance
            # Average Pace = 50 / 7.5 = 6.67 mins
            if ($TotalDistance == 0) {
                return 0;
            }
            $AveragePace = round(($TotalDuration / $TotalDistance), 1);
            return  $AveragePace;
        } else if (!$isDuration && !$isPace) {
            # Method 3: To find Average Pace
            # If user uses Distance and Speed:

            foreach ($exercises as $key => $log) {
                # Step 1 – Find lap Duration
                # Duration = (Distance / Speed) x 60
                $Duration = round((($log['distance'] / $log['speed']) * 60), 4);
                $allDurationArr[] = $Duration;
                $allDistanceArr[] = $log['distance'];
            }
            # Step 2 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hr x 60) + (Total Duration in mins) + (Total Duration in secs / 60)
            $TotalDuration = round(array_sum($allDurationArr), 4);

            # Step 3 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            $TotalDistance = array_sum($allDistanceArr);

            # Step 4 – Find Average Pace
            # Average Pace = Total Duration / Total Distance
            $AveragePace = round(($TotalDuration / $TotalDistance), 1);
            return $AveragePace;
        } else if (!$isDuration && $isPace) {
            # Method 4: To find Average Pace
            # If user uses Distance and Pace:

            foreach ($exercises as $key => $log) {
                # Step 1 - Convert Pace to seconds
                # Pace to seconds = (mins x 60) + secs
                $paceArr = explode(':', $log['pace']);
                $PaceToSeconds = ($paceArr[0] * 60)  + $paceArr[1];

                # Step 2 – Find lap Speed
                # Speed = 3600 / Pace in seconds
                $Speed = round((3600 / $PaceToSeconds), 1);

                # Step 3 – Find lap Duration (in mins)
                # Duration = (Distance / Speed) x 60
                # Duration (Lap 1) = (3.5 / 10.00) x 60 = 21.0000 (4 decimals place)
                $Duration = round((($log['distance'] / $Speed) * 60), 4);
                $allDurationArr[] = $Duration;
                $allDistanceArr[] = $log['distance'];
            }

            # Step 4 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hr x 60) + (Total Duration in mins) + (Total Duration in secs / 60)
            $TotalDuration = round(array_sum($allDurationArr), 4);

            # Step 5 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            $TotalDistance = array_sum($allDistanceArr);

            # Step 6 – Find Average Pace
            # Average Pace = Total Duration / Total Distance
            $AveragePace = round(($TotalDuration / $TotalDistance), 2);
            return $AveragePace;
        }
    }

    /**
     * calculatePaceCalculationGuidForOTHER
     *
     * @param  mixed $exercises
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculatePaceCalculationGuidForOTHER($exercises)
    {
        $totalDurationMinutes = 0;

        $isDuration = isset($exercises[0]['duration']);
        $isPace = isset($exercises[0]['pace']);

        $addDurationArr = [];
        $addDistanceArr = [];
        $addRestArr = [];

        if (!$isDuration && $isPace) {
            # Method 1 If user uses Distance and Pace:

            foreach ($exercises as $key =>  $log) {

                # Step 1 - Convert Pace to seconds
                # Pace to seconds = (mins x 60) + secs
                # Pace to seconds (Lap 1) = (3 x 60) + 30 = 210
                $paceArr = explode(':', $log['pace']);
                $PaceToSeconds = ($paceArr[0] * 60) + $paceArr[1];

                # Step 2 – Find lap Speed
                # Speed = 60 / Pace in seconds
                # Speed (Lap 1) = 60 / 210 = 0.29 km/min (2 decimals place)
                $Speed = round((60 / $PaceToSeconds), 2);

                # Step 3 – Find lap Duration (in mins)
                # Duration = Distance / Speed
                # Duration (Lap 1) = 3.5 / 0.29 = 12.0690 (4 decimals place)
                $Duration = round(($log['distance'] / $Speed), 4);
                $addDurationArr[] = $Duration;
                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $addRestArr[] = $restArr[0] + ($restArr[1] * 60);
                }
            }

            # Step 4 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hr x 60) + (Total Duration in mins) + (Total Duration in secs / 60)
            # Total Duration = (0 x 60) + (12.0690 + 4.5455) + (0 / 60) = (0) + () + (0)
            # = 16.6145 mins (4 decimals place)
            $TotalDuration = array_sum($addDurationArr) + array_sum($addRestArr);
            $TotalDuration = round($TotalDuration, 4);

            # Step 5 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 3.5 + 2.0 = 5.5 km
            $TotalDistance = collect($exercises)->sum('distance');

            # Step 6 – Find Average Pace
            # Average Pace = ((500 / (Total Distance x 1000)) x Total Duration
            # Average Pace = ((500 / (5.5 x 1000)) x 16.6145 = (500 / 5500) x 16.6145
            # = 0.0910 x 16.6145   = 1.51 mins/ 500m
            $AveragePace = ((500 / ($TotalDistance  * 1000))) * $TotalDuration;
            return $AveragePace;

            # Step 7 – Convert fraction to time
            # Convert fraction to time = (1.51 – 1) x 60
            # = 0.51 x 60
            # = 30.6 = 31 secs (whole number)
            # Average Pace (in time format) = 1 mins 31 secs = 1:31 mins/500m
            # Step 8 – Find Average Speed
            # Average Speed = 500 / Average Pace in fraction
            # = 500 / 1.51
            # = 331 m/min (whole number)
        } else if ($isDuration && $isPace) {
            # Method 2 If user uses Duration and Pace:

            foreach ($exercises as $key => $log) {
                if (isset($log['pace'])) {
                    # Step 1 - Convert Pace to seconds
                    # Pace to seconds = (mins x 60) + secs
                    # Pace to seconds (Lap 1) = (2 x 60) + 0 = 120
                    $paceArr = explode(':', $log['pace']);
                    $PaceToSeconds = ($paceArr[0] * 60) + $paceArr[1];

                    # Step 2 – Find lap Speed
                    # Speed = 60 / Pace in seconds
                    # Speed (Lap 1) = 60 / 120 = 0.50 km/min (2 decimals place)
                    $Speed = 60 / $PaceToSeconds;

                    # Step 3 – Find lap Distance
                    # Distance = ((Duration hr x 60) + Duration mins) x Speed
                    # Distance (Lap 1) = ((0 x 60) + 30) x 0.50 = 30 x 0.5 = 15.0 km (1 decimal)
                    $durationArr = explode(':', $log['duration']);
                    $duration = ($durationArr[0] * 60) + $durationArr[1] + ($durationArr[2] / 60);
                    $Distance = round(($duration * $Speed), 1);
                    $addDistanceArr[] = $Distance;
                    $addDurationArr[] = $duration;

                    if (isset($log['rest'])) {
                        $restArr = explode(':', $log['rest']);
                        $addRestArr[] = $restArr[0] + ($restArr[1] / 60);
                    }
                }
            }

            # Step 4 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            # Total Distance = 15.0 + 8.6 = 23.6 km
            $TotalDistance = array_sum($addDistanceArr);

            # Step 5 – Find Total Duration (in mins) – Total Duration should Include ‘Rest’ (if available)
            # Total Duration = (Total Duration in hr x 60) + (Total Duration in mins) + (Total Duration in secs / 60)
            $TotalDuration = round(($TotalDistance + array_sum($addRestArr)), 4);

            # Step 6 – Find Average Pace
            # Average Pace = ((500 / (Total Distance x 1000)) x Total Duration
            # Average Pace = ((500 / (23.6 x 1000)) x 45.0000
            # = (500 / 23600) x 45.0000
            # = 0.0212 x 45.0000
            # = 0.95 mins/ 500m
            $AveragePace = ((500 / ($TotalDistance * 1000))) * $TotalDuration;

            return $AveragePace;
            # Step 7 – Convert fraction to time
            # Convert fraction to time = (0.95 – 0) x 60
            # = 0.95 x 60 = 57.0 = 57 secs (whole number)

            # Average Pace (in time format) = 0 mins 57 secs = 00:57 mins/500m
            # Step 8 – Find Average Speed
            # Average Speed = 500 / Average Pace in fraction
            # = 500 / 0.95
            # = 526 m/min (whole number)
        }
    }

    /**
     * calculatePaceCalculationGuidForSwimming => Average Pace Calculation Guide For Swimming Only
     *
     * @param  mixed $exercises
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculatePaceCalculationGuidForSwimming($exercises, $totalDistance, $totalDurationMinute)
    {
        $totalDurationMinutes = 0;

        $isDuration = isset($exercises[0]['duration']);
        $isPace = isset($exercises[0]['pace']);

        $addAllDurationAndRest = [];

        if (!$isDuration && $isPace) {
            foreach ($exercises as $key => $log) {
                if (isset($log['pace'])) {
                    /**
                     * Method 1
                     * If user uses Distance and Pace:
                     */
                    # Step 1 - Convert Pace to seconds
                    # Pace to seconds = (mins x 60) + secs
                    $paceArr = explode(':', $log['pace']);
                    $paceToSec = ($paceArr[0] * 60) +  $paceArr[1];

                    # Step 2 – Find lap Speed
                    # Speed = 60 / Pace in seconds
                    $Speed = 60 / $paceToSec;

                    # Step 3 – Find lap Duration (in mins)
                    # Duration = Distance / Speed
                    $Duration = $log['distance'] / $Speed;

                    # Step 4 – Find Total Duration – Total Duration should Include ‘Rest’ (if available)
                    # Total Duration = (Total Duration in hr x 60) + (Total Duration in mins) + (Total Duration in secs / 60)
                    $restMinute = 0;
                    if (isset($log['rest'])) {
                        $restArr = explode(':', $log['rest']);
                        $restMinute = $restArr[0]  + ($restArr[1] / 60);
                    }
                    array_push($addAllDurationAndRest, round($Duration, 2), round($restMinute, 2));
                }
            }

            # Step 5 – Find Total Distance
            # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...
            $addAllDistance = collect($exercises)->sum('distance');
            $addAllDurationAndRestSum = array_sum($addAllDurationAndRest);

            # Step 6 – Find Average Pace
            # Average Pace = ((100 / (Total Distance x 1000)) x Total Duration
            $AveragePace = ((100 / ($addAllDistance * 1000) * $addAllDurationAndRestSum));
            $AveragePace = round($AveragePace, 1);

            # Step 7 – Convert fraction to time
            # Convert fraction to time = (0.27 – 0) x 60
            $AveragePaceFractionTime = ($AveragePace - 0) * 60;
            $AverageSpeed = 100 / $AveragePace;

            return $AveragePace;
        } else if ($isDuration && $isPace) {
            /**
             * Method 2
             * If user uses Duration and Pace:
             */
            $TotalDurationArr = [];
            $DistanceAddedArr = [];

            foreach ($exercises as $key => $log) {
                # Step 1 - Convert Pace to seconds
                # Pace to seconds = (mins x 60) + secs
                $paceArr = explode(':', $log['pace']);
                $PaceToSeconds = ($paceArr[0] * 60) + $paceArr[1];

                # Step 2 – Find lap Speed
                # Speed = 60 / Pace in seconds
                $Speed = round((60 / $PaceToSeconds), 1);

                # Step 3 – Find lap Distance
                # Distance = ((Duration in hr x 60) + (Duration in mins) + (Duration in secs / 60)) x Speed
                $DurationArr = explode(':', $log['duration']);
                $DistanceAddedArr[] = (($DurationArr[0] * 60) + ($DurationArr[1]) + ($DurationArr[2] / 60)) * $Speed;

                # Step 4 – Find Total Distance
                # Total Distance = (Lap 1 Distance) + (Lap 2 Distance) + (Lap 3 Distance) etc...

                # Step 5 – Find Total Duration (in mins) – Total Duration should Include ‘Rest’ (if available)
                # Total Duration = (Total Duration in hr x 60) + (Total Duration in mins) + (Total Duration in secs / 60)
                $totalDurationLap = ($DurationArr[0] * 60) + ($DurationArr[1]) + ($DurationArr[2] / 60);
                $Rest = 0;
                if (isset($log['rest'])) {
                    $restArr = explode(':', $log['rest']);
                    $Rest = $restArr[0] + ($restArr[1] / 60);
                }
                array_push($TotalDurationArr, $totalDurationLap, $Rest);
            }
            $TotalDistance = array_sum($DistanceAddedArr);
            $TotalDuration = array_sum($TotalDurationArr);

            # Step 6 – Find Average Pace
            # Average Pace = ((100 / (Total Distance x 1000)) x Total Duration
            $AveragePace = round(((100 / ($TotalDistance * 1000)) * $TotalDuration), 2);

            # Step 7 – Convert fraction to time
            # Convert fraction to time = (0.24 – 0) x 60
            $ConvertFractionToTime = ($AveragePace - 0) * 60;

            # Step 8 – Find Average Speed
            # Average Speed = 100 / Average Pace in fraction
            $AverageSpeed = 100 /  $AveragePace;

            return $AveragePace;
        }
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
        return $totalDurationMinutes;
    }

    /**
     * addAllDurationAndRestTimeFromExercise => Add it all duration and rest together and return Minutes( Duration + Rest )
     *
     * @param  mixed $exercises
     * @return void
     */
    public function addAllDurationAndRestTimeFromExercise($exercises)
    {
        $totalDurationMinutes = 0;
        foreach ($exercises as $key => $exercise) {
            $durationArray = explode(":", $exercise['duration']);
            $durationMinute = (
                ((int) $durationArray[0] * 60)  // hour to  minutes
                + ((int) $durationArray[1]) // minutes
                + ((int) $durationArray[2] / 60) // second to minutes
            );
            $restMinute = 0;
            if (isset($exercise['rest'])) {
                $restArray = explode(":", $exercise['rest']);
                $restMinute = (
                    ((int) $restArray[0]) // minutes
                    + ((int) $restArray[1] / 60) // second to minutes
                );
            }
            $totalDurationMinutes += ($durationMinute + $restMinute);
        }
        return $totalDurationMinutes;
    }

    /**
     * convertDurationToHours => Calculate hours from duration
     * "01:30:00" => 1.50 "hour"
     * @param  mixed $OriginalDuration
     * @return void
     */
    public function convertDurationToHours($OriginalDuration)
    {
        $durationArray = explode(':', $OriginalDuration);
        $totalDuration = round(
            (int) $durationArray[0]
                + ((int) $durationArray[1] / 60)
                + ((int) $durationArray[2] / 3600),
            4
        );
        return $totalDuration;
    }

    /**
     * convertDurationToMinutes
     *
     * @param  mixed $OriginalDuration
     * @return void
     */
    public function convertDurationToMinutes($OriginalDuration)
    {
        $durationArray = explode(':', $OriginalDuration);
        $totalDuration = round(
            (((int) $durationArray[0] * 60)
                + ((int) $durationArray[1])
                + ((int) $durationArray[2] / 60)),
            4
        );
        return round($totalDuration, 2);
    }

    /**
     * convertPaceNumberTo_M_S_format => convert pace number to mm:ss format
     *
     * @param  mixed $pace
     * @return void
     */
    public function convertPaceNumberTo_M_S_format(float $pace = 0.0)
    {
        // $pace =  9.90; // static check // no longer needed
        $pace = round($pace, 2);
        $avgPaceArr = explode('.', $pace);
        $floatPace = '0.' . ($avgPaceArr[1] ?? 0);
        $floatPace = $floatPace * 60;
        $floatPaceArr = explode('.', $floatPace);
        $paceFormatted = (in_array($avgPaceArr[0], range(0, 9)) ? $avgPaceArr[0] : $avgPaceArr[0])
            . ':'
            . (in_array($floatPaceArr[0], range(0, 9)) ? '0' . $floatPaceArr[0] : $floatPaceArr[0]);
        // dd('check what is pace ', $pace,  $paceFormatted);
        return $paceFormatted;
    }

    /**
     * getDistanceFromExerciseWatch
     *
     * @param  mixed $lastExerciseTotalDistance
     * @return void
     */
    public function getDistanceFromExerciseWatch($lastExerciseTotalDistance)
    {
        $lastExerciseTotalDistance = ($lastExerciseTotalDistance / 1000);
        $lastExerciseTotalDistanceFloor = floor($lastExerciseTotalDistance * 10) / 10; // "\(floor((value*10)) / 10)"
        return $lastExerciseTotalDistanceFloor;
    }

    /**
     * convertDurationMinutesToTimeFormat => convert minutes to time format
     *
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function convertDurationMinutesToTimeFormat($totalDurationMinute)
    {
        $format = "H:i:s";
        $seconds = (($totalDurationMinute ?? 0) * 60);
        $time = (gmdate($format, $seconds));
        $timeArr = explode(':', $time);
        $timeArr[0] = (int) $timeArr[0];
        $time = implode(':', $timeArr);
        return $time;
    }
}
