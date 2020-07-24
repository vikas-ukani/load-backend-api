<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use App\Http\Controllers\Controller;
use App\Supports\SummaryCalculationTrait;
use Illuminate\Support\Arr;

class RunCalculationsController extends Controller
{
    use SummaryCalculationTrait;

    protected $total_distance_unit;
    protected $avg_pace_unit;
    protected $elevation_gain_unit;
    protected $gradient_unit;
    protected $avg_speed_unit;

    public function __construct()
    {
        $this->total_distance_unit = "km";
        $this->avg_pace_unit = "min/km";
        $this->avg_speed_unit = "km/hr";
        $this->gradient_unit = "%";
        $this->elevation_gain_unit = "m";
    }

    /**
     * RUN ( IN | OUT )
     */
    public function generateRunCalculation($trainingLog)
    {

        /** set is Duration or not AND It's Activity */
        $activityCode = $trainingLog['training_activity']['code'];
        $isDuration = $trainingLog['exercise'][0]['duration'];

        $response = [];

        #  Calculate Distance By Given Start Lat-Long  and END lat-long
        # START Total Distance 
        /** get first from generated calculation */
        if (isset($trainingLog['generated_calculations'], $trainingLog['generated_calculations']['total_distance'])) {
            $response = array_merge($response, [
                'total_distance' => $trainingLog['generated_calculations']['total_distance'],
                'total_distance_unit' =>  $this->total_distance_unit
            ]);
        } else {
            $calculateTotalDistance = $this->calculateTotalDistance(
                $trainingLog,
                $activityCode,
                $isDuration
            );
            $response = array_merge($response, $calculateTotalDistance);
        }
        # END Total Distance 

        /** If there are more than 1 inclination value in the log,
         * for example, 2 laps and 1 lap is set to 1% and another lap is set to 3%,
         * we will take an average of that. Meaning (1+3)/2 = 2% 
         */
        $numberOfLaps = count($trainingLog['exercise']);
        if ($numberOfLaps == 1) {
            $response['inclination'] = round(($trainingLog['exercise'][0]['percentage']), 2);
        } else {
            $svgInclination = collect($trainingLog['exercise'])->avg('percentage');
            $response['inclination'] = round(($svgInclination), 2);
            // $response['inclination'] = round(($svgInclination / 100), 2);
        }

        # START Total Duration 
        /** get first from generated calculation */
        if (isset($trainingLog['generated_calculations'], $trainingLog['generated_calculations']['total_duration'])) {
            $response = array_merge($response, [
                'total_duration' => $trainingLog['generated_calculations']['total_duration'],
                'total_duration_minutes' => $this->convertDurationToMinutes($trainingLog['generated_calculations']['total_duration'])
            ]);
        } else {
            $calculateDuration = $this->calculateDuration(
                $trainingLog
            );
            $response = array_merge($response, $calculateDuration);
        }
        # END Total Duration 


        # START Average Speed (can be either km/hr OR mile/hr, depending on the unit setting) 
        /** get first from generated calculation */
        if (isset($trainingLog['generated_calculations'], $trainingLog['generated_calculations']['avg_speed'])) {
            $response = array_merge($response, [
                'avg_speed' => $trainingLog['generated_calculations']['avg_speed'],
                'avg_speed_unit' => $this->avg_speed_unit
            ]);
        } else {
            $calculateAverageSpeed = $this->calculateAverageSpeed(
                $trainingLog['exercise'],
                $activityCode,
                $response['total_distance'],
                $response['total_duration_minutes']
            );
            $response = array_merge($response, $calculateAverageSpeed);
        }
        # END Average Speed


        # 3. START Average Pace 
        /** get first from generated calculation */
        if (isset($trainingLog['generated_calculations'], $trainingLog['generated_calculations']['avg_pace'])) {
            $response = array_merge($response, [
                'avg_pace' => $trainingLog['generated_calculations']['avg_pace'],
                'avg_pace_unit' =>  $this->avg_pace_unit
            ]);
        } else {
            $calculateAvgPace = $this->calculateAvgPace(
                $trainingLog['exercise'],
                $response['total_distance'],
                $response['total_duration_minutes'],
                $activityCode
            );
            $response = array_merge($response, $calculateAvgPace);
        }
        # 3. END Average Pace 

        # Start Elevation Gain
        $calculateElevationGain = $this->calculateElevationGain(
            $trainingLog['exercise'],
            $activityCode
            // $response['total_distance'],
            // $response['total_duration_minutes'],

        );
        $response = array_merge($response, $calculateElevationGain);
        # End Elevation Gain

        # Start Gradient (%)
        $calculateGradient = $this->calculateGradient(
            $trainingLog['exercise'],
            $activityCode,
            $response['elevation_gain'],
            $response['total_distance']
        );
        $response = array_merge($response, $calculateGradient);
        # End Gradient (%)

        /** Step 1 (Find m/min):
         * Using the average speed (km/hr or miles/ hr), 
         * convert it to m/min using one of the equation (depending on user’s unit setting):
         *      a. m/min = speed in km/hr x 16.7
         *      b. m/min = speed in miles/hr x 26.8 
         */
        $m_min = $response['avg_speed'] * 16.7;

        /** Step 2 (Find VO2):
         * VO2 = (0.2 x speed) + (0.9 x speed x *inclination) + 3.5 
         * *Inclination of 1% = 0.01
         */
        // $inclination = round(($response['inclination'] / 100), 2); // OLD
        // $VO2 = (0.2 * $m_min) + (0.9 * $m_min *  $inclination) + 3.5; // OLD 
        $VO2 = (0.2 * $m_min) + (0.9 * $m_min *  ($response['gradient'] / 100)) + 3.5; // NEW
        $response['VO2'] = $VO2 = round($VO2, 2);

        /** Step 3 (Find MET value): 
         * MET = VO2 / 3.5 
         */
        $response['MET'] = $MET = round(($VO2 / 3.5), 2);

        /** Step 4 (Find kcal/min):
         * kcal/min = (MET x 3.5 x body mass in kg) / 200 
         */
        $response['BMI'] = $BMI = round(((($trainingLog['user_detail']['weight'] ?? 0) / ($trainingLog['user_detail']['height'] ?? 0)  / ($trainingLog['user_detail']['height'] ?? 0)) * 10000), 2);
        $response['kcal_min'] = $kcal_min = round((($MET * 3.5 * $BMI) / 200), 2);

        /** Step 5 (Final total kcal):
         * Total kcal = (kcal/min) x total duration in mins 
         */
        $response['total_kcal'] = round(($kcal_min * $response['total_duration_minutes']), 2);
        // dd('check All Counts ', "total_kcal", $response['total_kcal'],  "BMI", $BMI, "kcal_min", $kcal_min,  "MET ", $MET,  $response['VO2'], "m_min", $m_min, "inclination",  $response['inclination']);

        return $response;
    }

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
                 * Lap 1 Duration (in hour) → 20 / 60 = 0.3333 (4 decimals place)
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
    
    /**
     * calculateTotalDistance
     *
     * @param  mixed $trainingLog
     * @param  mixed $activityCode
     * @param  mixed $isDuration
     * @return void
     */
    public function calculateTotalDistance($trainingLog, $activityCode, $isDuration)
    {
        # Start Total Distance Calculation ------------------------------------------------------------------------
        $total_distance = 0;

        /** calculate from App Side Using Watch "B" */
        $exerciseTotalDistanceArray = collect($trainingLog['exercise'])->whereNotIn('total_distance',  ['0', null, 0])->pluck('total_distance')->all();
        if (isset($exerciseTotalDistanceArray) && is_array($exerciseTotalDistanceArray) && count($exerciseTotalDistanceArray) > 0) {
            /** all meter data convert to KM */
            $total_distance = Arr::first($exerciseTotalDistanceArray);
            $total_distance = round(($total_distance * 0.001), 2);
            $total_distance_code = "B";
        }
        // dd('Check B Here',  $total_distance, $exerciseTotalDistanceArray,  $trainingLog['exercise']);

        # Apply For "C" 
        if ($total_distance == 0) {
            $total_distance_code = "C";
            $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
        }

        #  "A" Calculate
        if ($activityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            // NOTE - Remain To Test
            $total_distance = $this->getTotalDistanceFromStartEndLatitudeLongitude($trainingLog);
            $total_distance_code = "A";
        }

        # Use D here if all distance is 0  "D"
        if ($total_distance == 0) {
            $total_distance_code = "D";
            $total_distance = collect($trainingLog['exercise'])->sum('distance');
        }
        # End Total Distance Calculation  ------------------------------------------------------------------------
        return [
            'total_distance' => round($total_distance, 2),
            'total_distance_unit' =>  $this->total_distance_unit,
            'total_distance_code' => $total_distance_code
        ];
    }

    /**
     * calculateDuration => updated duration calculations.
     *
     * @param  mixed $trainingLog
     * @return void
     */
    public function calculateDuration($trainingLog)
    {
        # Calculate Duration From Exercise using start_time and end_time
        $totalDurationMinute = 0;
        /** get total_minute from start_time and end_time */
        $totalDurationMinute = $this->totalDurationMinute($trainingLog);

        /** 
         * Calculate Total Duration Outdoor and Indoor: A → B → C
         */
        if (
            isset($totalDurationMinute) &&
            in_array(($totalDurationMinute * 60),
                range(TRAINING_WORKOUT_STOP_IMMEDIATELY_MIN_SECOND, TRAINING_WORKOUT_STOP_IMMEDIATELY_MAX_SECOND)
            )
        ) {
            /** check minute less then  means immediately stop workout */
            # C OR D equation
            $ifDistance = $trainingLog['exercise'][0]['distance'];
            if (isset($ifDistance)) {
                # C Use here
                $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
                $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60)));
                // dd('final is ', $totalDurationMinute, $response['total_duration']);
                $totalDurationMinuteCode = "C";
            } else {
                # D Use here | all add it together duration 
                // $totalDurationMinute = $this->addAllDurationTimeFromExercise($trainingLog['exercise']);  // OLD
                $totalDurationMinute = $this->addAllDurationAndRestTimeFromExercise($trainingLog['exercise']);  // NEW
                $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0) * 60)));
                $totalDurationMinuteCode = "D";
            }
            // dd('check is duration',$trainingLog['exercise'][0] );
        } else {
            # A equation 
            $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0) * 60)));
            $totalDurationMinuteCode = "A";
        }
        $response['total_duration_in_minute'] = $totalDurationMinute;
        // $response['total_duration'] = ($totalDurationMinute == 0) ? "00:00:00" :(gmdate("H:i:s", (($totalDurationMinute ?? 0) * 60)));
        // dd('total rpm ', $total_rpm, $trainingLog['exercise']);
        return [
            'total_duration_minutes' => round($totalDurationMinute, 2),
            'total_duration' => $this->convertDurationMinutesToTimeFormat($totalDurationMinute),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
    }
    
    /**
     * calculateAverageSpeed => calculate average speed updated
     *
     * @param  mixed $exercises
     * @param  mixed $activityCode
     * @param  mixed $total_distance
     * @param  mixed $total_duration_minutes
     * @return void
     */
    public function calculateAverageSpeed($exercises, $activityCode, $total_distance, $total_duration_minutes)
    {
        /** SPEED START ****************************************** */
        $avg_speed = 0;
        // $response['avg_speed'] = $totalDurationMinute == 0 ? 0 : round(($response['total_distance'] / $totalDurationMinute), 2);
        $isDuration = $exercises[0]['duration'];

        # C) If the user click on the ‘Complete’ button to log the workout, use equation (Please see
        // Average Speed Calculation Guide).
        $avg_speed_code = "C";
        $avg_speed = $this->calculateAverageSpeedGuide(
            $exercises,
            $total_distance,
            $total_duration_minutes
        );

        # B) Record the Average Speed value recorded from the exercise watch (if available).

        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            # A) Use phone GPS/tracker and apply this equation:
            // Average Speed = Total Distance (in km) / Total Duration (convert to minutes) (only for Outdoor)
            $avg_speed = $total_duration_minutes == 0 ? 0 : round(($total_distance / $total_duration_minutes), 2);
            $avg_speed_code = "A";
        }

        $data = [
            'avg_speed' =>      round($avg_speed, 2),
            'avg_speed_unit' =>      $this->avg_speed_unit,
            'avg_speed_code' =>      $avg_speed_code
        ];
        return $data;
    }

    public function calculateAvgPace($exercises, $totalDistance, $totalDurationMinute, $activityCode)
    {
        $avg_pace = 0;
        $avg_pace_code = null;
        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            # A) Use phone location and motion sensors (GPS + Accelerometer) (only for Outdoor)
            // $avg_pace_code = "A";
        }

        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_INDOOR) {
            # B) Use phone motion sensor (Accelerometer) (only for Indoor)
            // $avg_pace_code = "B";
        }

        if ($avg_pace == 0) {
            # C) Record the Average Pace value recorded from the exercise watch (if available).
            // $avg_pace_code = "C";
        }

        if ($avg_pace == 0 && $activityCode == TRAINING_ACTIVITY_CODE_RUN_INDOOR) {
            # D) For Indoor (if no movement detected) use OR if the user click on the ‘Complete’ button
            # to log the workout, use equation (Please see Average Pace Calculation Guide).
            $avg_pace = $this->calculatePaceCalculationGuid(
                $exercises,
                $totalDistance,
                $totalDurationMinute
            );
            $avg_pace_code = "D";
        }
        $avg_pace = $this->convertPaceNumberTo_M_S_format($avg_pace);

        // "avg pace" End Calculate  -------------------------------------------
        return [
            'avg_pace' => $avg_pace ?? null,
            'avg_pace_unit' =>  $this->avg_pace_unit,
            'avg_pace_code' => $avg_pace_code
        ];
    }

    public function calculateElevationGain($exercises, $activityCode)
    {
        $elevation_gain = 0;
        $elevation_gain_code = null;

        if ($elevation_gain == 0 && $activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            # Out Door
            // Pending
        } else {
            # In Door
            // Pending
        }

        return [
            'elevation_gain' => $elevation_gain ?? null,
            'elevation_gain_unit' =>  $this->elevation_gain_unit,
            'elevation_gain_code' => $elevation_gain_code
        ];
    }


    /**
     * calculateGradient => calculate gradient
     *
     * @param  mixed $exercises
     * @param  mixed $activityCode
     * @param  mixed $elevation_gain
     * @param  mixed $total_distance
     * @return array
     */
    public function calculateGradient($exercises, $activityCode, $elevation_gain, $total_distance)
    {
        $gradient = 0;
        $gradient_code = null;
        // dd('check gradient', $activityCode, $exercises);

        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            # A) Find Gradient with equation:
            /**
             * Step 1) Convert Total Distance unit (km or miles) to meters or yards
             * Step 2) Lvl = (Elevation gain/ Step 1) x 100
             */
            $total_distance_in_meters = $total_distance // convert to km first
                * 1000; // convert km to meters
            $gradient = ($elevation_gain / $total_distance_in_meters) * 100;
            $gradient_code = "A";

            # B) If Elevation Gain value is not available, Gradient will show '-'
            if ($gradient == 0 || in_array($elevation_gain, [0, 0.0, null, ''])) {
                $gradient = 0;
                $gradient_code = "B";
            }
        }
        if ($gradient == 0) {
            # C) For Indoor use OR if the user click on the ‘Complete’ button to log the workout, use equation:
            /**
             * Step 1) Total Gradient = Add all the % value(s) in the training log.
             * Step 2) Gradient = Total Gradient / Total lap(s)
             */
            $totalGradient = collect($exercises)->sum('percentage');
            $gradient = $totalGradient / count($exercises);
            $gradient_code = "C";
        }



        return [
            'gradient' => $gradient ?? null,
            'gradient_unit' =>  $this->gradient_unit,
            'gradient_code' => $gradient_code
        ];
    }
}
