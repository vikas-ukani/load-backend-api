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

    /**
     * __construct => initially called
     * 
     *
     * @return void
     */
    public function __construct()
    {
        $this->total_distance_unit = "km";
        $this->avg_pace_unit = "min/km";
        $this->avg_speed_unit = "km/hr";
        $this->gradient_unit = "%";
        $this->elevation_gain_unit = "m";
    }

    /**
     * generateRunCalculation =>  RUN ( IN | OUT )
     *
     * @param  mixed $trainingLog
     * @return void
     */
    public function generateRunCalculation($trainingLog)
    {

        /** set is Duration or not AND It's Activity */
        $activityCode = $trainingLog['training_activity']['code'];
        $isDuration = $trainingLog['exercise'][0]['duration'];

        $response = [];

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

        # Calculate Active Duration and Minutes || replaced by total_duration
        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            $deActiveDuration = $trainingLog['exercise'][0]['deactive_duration']  ?? 0;
            $calculateActiveDuration = $this->calculateActiveDuration($response['total_duration_minutes'], $deActiveDuration);
            $response = array_merge($response, $calculateActiveDuration);
        }

        # Calculate Distance By Given Start Lat-Long  and END lat-long
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

        # START Average Speed (can be either km/hr OR mile/hr, depending on the unit setting) 
        /** get first from generated calculation */
        if (isset($trainingLog['generated_calculations'], $trainingLog['generated_calculations']['avg_speed'])) {
            $response = array_merge($response, [
                'avg_speed' => round($trainingLog['generated_calculations']['avg_speed'], 1),
                'avg_speed_unit' => $this->avg_speed_unit
            ]);
        } else {
            $calculateAverageSpeed = $this->calculateAverageSpeed(
                $trainingLog['exercise'],
                $activityCode,
                $response['total_distance'],
                $response['active_duration_minutes'] ?? $response['total_duration_minutes']
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
                $response['active_duration_minutes'] ?? $response['total_duration_minutes'],
                $activityCode
            );
            $response = array_merge($response, $calculateAvgPace);
        }
        # 3. END Average Pace 

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
        }

        # Start Elevation Gain
        $calculateElevationGain = $this->calculateElevationGain(
            $trainingLog['exercise'],
            $activityCode
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

        # start Total kcal
        $calculateTotalKcal = $this->calculateTotalKcal(
            $trainingLog['exercise'],
            $response['avg_speed'],
            $response['gradient'],
            $trainingLog['user_detail']['weight'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateTotalKcal);
        # end Total kcal

        return $response;
    }

    /**
     * calculateDuration => updated duration calculations.
     *
     * @param  mixed $trainingLog
     * @return void
     */
    public function calculateDuration($trainingLog)
    {
        # Outdoor and Indoor: A, B → C OR D

        # Calculate Duration From Exercise using start_time and end_time
        $totalDurationMinute = 0;

        # A) If the user click on the ‘Start’ button, use phone tracker (when user starts the workout log to when the workout log ends).
        $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);
        $totalDurationMinuteCode = "A";

        if (!$isCompleteButton && $totalDurationMinute == 0) {
            # B) If the user click on the ‘Start’ button, 
            # record the Total Duration value recorded from the exercise watch (if available).
            # $totalDurationMinuteCode = "B";
        }

        # Client said remove remove this 30 second condition
        // if (in_array(($totalDurationMinute * 60), range(TRAINING_WORKOUT_STOP_IMMEDIATELY_MIN_SECOND, TRAINING_WORKOUT_STOP_IMMEDIATELY_MAX_SECOND)) ) {
        $ifDistance = $trainingLog['exercise'][0]['distance'];
        if ($isCompleteButton && isset($ifDistance)) {
            # C) If the user click on the ‘Complete’ button to log the workout, use equation 
            # (If user use ‘Distance’ in the log. Please see Duration Calculation Guide).
            $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
            $totalDurationMinuteCode = "C";
        } else if ($isCompleteButton) {
            # D) If the user click on the ‘Complete’ button to log the workout, use the duration 
            # (If user use ‘Duration’ in the log):
            # Add all the Duration (including Rest) parameters in the log.
            $totalDurationMinute = $this->addAllDurationAndRestTimeFromExercise($trainingLog['exercise']);  // NEW
            $totalDurationMinuteCode = "D";
        }
        // }
        $totalDurationMinute = round($totalDurationMinute, 1);
        return [
            'total_duration_minutes' => $totalDurationMinute,
            'total_duration' => $this->convertDurationMinutesToTimeFormat($totalDurationMinute),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
    }

    /**
     * calculateActiveDuration
     *
     * @param  mixed $totalDurationMinute
     * @param  mixed $deActiveDuration
     * @return void
     */
    public function calculateActiveDuration($totalDurationMinute, $deActiveDuration = 0)
    {
        $deActiveDurationMinute = ($deActiveDuration ?? 0) / 60; // to convert into minute
        $totalDurationMinute = round($totalDurationMinute, 2);
        $deActiveDurationMinute = round($deActiveDurationMinute, 2);
        $activeDurationMinute = round(($totalDurationMinute - $deActiveDurationMinute), 2);

        return [
            'active_duration_minutes' => $activeDurationMinute,
            'active_duration' => $this->convertDurationMinutesToTimeFormat($activeDurationMinute)
        ];
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
        # Outdoor: A, C → D OR E
        # Indoor: B, C → D OR E

        $total_distance = 0;

        $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        # A) If the user click on the ‘Start’ button,
        # use phone location and motion sensors (GPS + Accelerometer) (only for Outdoor)
        if (!$isCompleteButton && $activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            // $total_distance = $this->getTotalDistanceFromStartEndLatitudeLongitude($trainingLog);
            // $total_distance_code = "A";
        } else if (!$isCompleteButton && $activityCode == TRAINING_ACTIVITY_CODE_RUN_INDOOR) {
            # B) If the user click on the ‘Start’ button, 
            # use phone motion sensor (Accelerometer) (only for Indoor).
            if (isset($exerciseTotalDistanceArray) && is_array($exerciseTotalDistanceArray) && count($exerciseTotalDistanceArray) > 0) {
                /** all meter data convert to KM */
                $total_distance = Arr::first($exerciseTotalDistanceArray); // get from first lap
                $total_distance = round(($total_distance * 0.001), 1);
                $total_distance_code = "B";
            }
        }

        # For A and C case mixed.
        $lastExerciseTotalDistance = collect($trainingLog['exercise'])->whereNotIn('total_distance', ['0', 0, null])->pluck('total_distance')->first();
        # C) If the user click on the ‘Start’ button, 
        # record the Total Distance value recorded from the exercise watch (if available).
        if (!$isCompleteButton && $total_distance == 0 && isset($lastExerciseTotalDistance)) {
            $total_distance = $this->getDistanceFromExerciseWatch($lastExerciseTotalDistance); // function in summary controller
            $total_distance_code = "C";
        }

        if ($isCompleteButton) {
            # D) If the user click on the ‘Complete’ button to log the workout, 
            # use equation (If user use ‘Duration’ in the log. Please see Distance Calculation Guide).
            if ($total_distance == 0) {
                $total_distance_code = "D";
                $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
            }

            # E) If the user click on the ‘Complete’ button to log the workout, 
            # use equation (If user use ‘Distance’ in the log):
            # Add all the Distance parameters in the log.
            if ($total_distance == 0) {
                $total_distance_code = "E";
                $total_distance = collect($trainingLog['exercise'])->sum('distance');
            }
        }

        $data = [
            'total_distance' => round($total_distance, 1),
            'total_distance_unit' =>  $this->total_distance_unit,
            'total_distance_code' => $total_distance_code ?? ''
        ];
        return $data;
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
        # Outdoor: A, C → D
        # Indoor: B, C → D

        $avg_speed = 0;
        $isDuration = $exercises[0]['duration'];

        /** if $totalDurationMinute  is 0 Means *COMPLETE* button clicked */
        $totalDurationMinute = $this->totalDurationMinute(['exercise' => $exercises]);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);
        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            $deActiveDuration = $exercises[0]['deactive_duration'] ?? 0;
            $calculateActiveDuration = $this->calculateActiveDuration($total_duration_minutes, $deActiveDuration);
            $res = array_merge([], $calculateActiveDuration);
            $totalDurationMinute =  $res['active_duration_minutes'];
        }

        # A) If the user click on the ‘Start’ button, use phone motion sensor, 
        # use phone location and motion sensors (GPS + Accelerometer) 
        # (only for Outdoor)
        if (!$isCompleteButton &&  $activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            /** use this told by yash */
            $avg_speed = $total_distance / ($totalDurationMinute / 60); // minute to hr 
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            // if (isset($avg_pace)) { 
            //     # convert pace to speed
            //     $avg_speed = (60 / $avg_pace); 
            // }
            $avg_speed_code = "A";
        }
        if (!$isCompleteButton &&  $activityCode = TRAINING_ACTIVITY_CODE_RUN_INDOOR) {
            /** use this told by yash */
            $avg_speed = $total_distance / ($totalDurationMinute / 60); // minute to hr
            # B) Use phone motion sensor (Accelerometer) (only for Indoor)
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            // if (isset($avg_pace)) {
            //     # convert pace to speed
            //     $avg_speed = (60 / $avg_pace);
            // }
            $avg_speed_code = "B";
            // dd(
            //     'check data speed',
            //     $total_distance,
            //     $totalDurationMinute / 60,
            //     $total_distance / ($totalDurationMinute / 60),
            //     $avg_speed,
            //     $avg_speed_code
            // );
        }
        $avg_speed = $avg_speed ?? 0;
        if ($avg_speed == 0) {
            # C) Record the Average Speed value recorded from the exercise watch (if available).
            // $avg_speed_code = "C";
        }

        if ($avg_speed == 0 && $isCompleteButton) {
            # D) If the user click on the ‘Complete’ button to log the workout, use equation (Please see
            # Average Speed Calculation Guide).
            $avg_speed_code = "D";
            $avg_speed = $this->calculateAverageSpeedGuide(
                $exercises,
                $total_distance,
                $total_duration_minutes
            );
        }

        if ($avg_speed == 0 && $activityCode == TRAINING_ACTIVITY_CODE_RUN_INDOOR) {
            # E) Only for Indoor: If the user click on the ‘Start’ button and if no movement detected,
            # leave the value empty. Allow the user to key in the value manually (Step 5).
            $avg_speed_code = "E";
        }
        $avg_speed = round($avg_speed, 1);
        $data = [
            'avg_speed' => $avg_speed,
            'avg_speed_unit' => $this->avg_speed_unit,
            'avg_speed_code' => $avg_speed_code
        ];

        return $data;
    }

    /**
     * calculateAvgPace
     *
     * @param  mixed $exercises
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @param  mixed $activityCode
     * @return void
     */
    public function calculateAvgPace($exercises, $totalDistance, $total_duration_minutes, $activityCode)
    {
        $avg_pace = 0;
        $avg_pace_code = null;

        /** if $totalDurationMinute  is 0 Means *COMPLETE* button clicked */
        $totalDurationMinute = $this->totalDurationMinute(['exercise' => $exercises]);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);
        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            $deActiveDuration = $exercises[0]['deactive_duration']  ?? 0;
            $calculateActiveDuration = $this->calculateActiveDuration($total_duration_minutes, $deActiveDuration);
            $res = array_merge([], $calculateActiveDuration);
            $total_duration_minutes =  $res['active_duration_minutes'];
        }

        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            # A) Use phone location and motion sensors (GPS + Accelerometer) (only for Outdoor)
            /** told by yash */
            // $avg_speed = $totalDistance / $total_duration_minutes;
            // $avg_pace = 60 / $avg_speed;
            $avg_pace = $totalDistance == 0 ? 0 : ($total_duration_minutes / $totalDistance);
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            $avg_pace_code = "A";
        } else if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_INDOOR) {
            # B) Use phone motion sensor (Accelerometer) (only for Indoor)
            /** told by yash */
            // $avg_speed = $totalDistance / $total_duration_minutes;
            // $avg_pace = 60 / $avg_speed;
            $avg_pace = $totalDistance == 0 ? 0 : ($total_duration_minutes / $totalDistance);
            $avg_pace = round($avg_pace, 4);

            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            $avg_pace_code = "B";
        }

        if ($avg_pace == 0) {
            # C) Record the Average Pace value recorded from the exercise watch (if available).
            // $avg_pace_code = "C";
        }

        if ($isCompleteButton && $avg_pace == 0) {
            # D) If the user click on the ‘Complete’ button to log the workout, 
            # use equation (Please see Average Pace Calculation Guide).
            $avg_pace = $this->calculatePaceCalculationGuid(
                $exercises,
                $totalDistance,
                $total_duration_minutes
            );
            $avg_pace_code = "D";
        }

        if (!$isCompleteButton && $avg_pace == 0 && $activityCode == TRAINING_ACTIVITY_CODE_RUN_INDOOR) {
            # E) Only for Indoor: If the user click on the ‘Start’ button and if no movement detected,
            # leave the value empty. Allow the user to key in the value manually (Step 5).
            $avg_pace = 0;
            $avg_pace_code = "E";
        }
        $avg_pace = $this->convertPaceNumberTo_M_S_format($avg_pace);

        // "avg pace" End Calculate  -------------------------------------------
        return [
            'avg_pace' => $avg_pace ?? null,
            'avg_pace_unit' =>  $this->avg_pace_unit,
            'avg_pace_code' => $avg_pace_code
        ];
    }

    /**
     * calculateElevationGain => Run Outdoor Only.
     *
     * @param  mixed $exercises
     * @param  mixed $activityCode
     * @return void
     */
    public function calculateElevationGain($exercises, $activityCode)
    {
        $elevation_gain = 0;
        $elevation_gain_code = null;

        // Implement while app side is done ...
        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            /*** get elevation_gain from Exercise Last Lap */

            // catch the elevation gain **** 
            $elevation_gain = isset(collect($exercises)->last()['elevation_gain']) ? collect($exercises)->last()['elevation_gain'] : 0;
            # A) Use phone location sensor (Barometer) (only for Outdoor)
            // Pending
            // $elevation_gain_code = 'A';

            if ($elevation_gain == 0) {
                # B) Record the Elevation Gain value recorded from the exercise watch or phone with
                # barometric altimeter (if available).
                // $elevation_gain = 0;
                $elevation_gain_code = 'B';
            }

            if ($elevation_gain == 0 && $activityCode == TRAINING_ACTIVITY_CODE_RUN_INDOOR) {
                # C) For Indoor use OR if the user click on the ‘Complete’ button to log the workout, Elevation
                # Gain will show ‘-‘
                $elevation_gain =  0;
                $elevation_gain_code = 'C';
            }
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

        if ($activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            # A) Find Gradient with equation:
            /**
             * Step 1) Convert Total Distance unit (km or miles) to meters or yards
             * Step 2) Lvl = (Elevation gain/ Step 1) x 100
             */
            $total_distance_in_meters = $total_distance // convert to km first
                * 1000; // convert km to meters
            $gradient = $total_distance_in_meters == 0 ? 0 : ($elevation_gain / $total_distance_in_meters) * 100;
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
            'gradient' => round($gradient, 2) ?? 0,
            'gradient_unit' => $this->gradient_unit,
            'gradient_code' => $gradient_code
        ];
    }

    /**
     * calculateTotalKcal
     *
     * @param  mixed $exercises
     * @param  mixed $avg_speed
     * @param  mixed $gradient
     * @param  mixed $userWeight
     * @param  mixed $total_duration_minutes
     * @return void
     */
    public function calculateTotalKcal($exercises, $avg_speed, $gradient, $userWeight, $total_duration_minutes)
    {
        $total_kcal = 0;

        # A) Record the Total kcal value recorded from the exercise watch (if available).
        // $total_kcal_code = 'A';

        if ($total_kcal == 0) {
            # B) Find Total kcal with equation:
            $total_kcal_code = 'B';

            /** NOTE: If the user’s weight setting is in lbs (Under Settings → Training → Imperial), convert it
             * to kg first and round off to 1 decimal place before using the value in Step 4:
             * Ex. 1lbs = 0.45359237 kg 
             */

            /**
             * Step 1 (Find m/min):
             * Using the average speed (km/hr or miles/ hr), convert it to m/min using one of the equation
             * (depending on user’s unit setting):
             * a. m/min = speed in km/hr x 16.7
             * b. m/min = speed in miles/hr x 26.8
             */
            $m_min = $avg_speed * 16.7; // default set for km

            /**
             * Step 2 (Find VO2):
             * VO2 = (0.2 x step 1) + (0.9 x step 1 x (gradient/100)) + 3.5
             */
            $VO2 = (0.2 * $m_min) + (0.9 * $m_min * ($gradient / 100)) + 3.5;

            /**
             * Step 3 (Find MET value):
             * MET = Step 2/ 3.5
             */
            $MET  = $VO2 / 3.5;

            /**
             * Step 4 (Find kcal/min):
             * kcal/min = (Step 3 x 3.5 x user’s weight in kg) / 200
             */
            $kcal_min_4 = ($MET * 3.5 * $userWeight) / 200;

            /**
             * Step 5 (Final total kcal):
             * Total kcal = Step 4 x Total Duration in mins
             */
            $total_kcal = $kcal_min_4 * $total_duration_minutes;
        }

        return [
            "total_kcal" => round($total_kcal, 2),
            "total_kcal_code" => $total_kcal_code
        ];
    }


    /**
     * findTotalDistanceUsingDuration
     *
     * @param  mixed $exercises
     * @return void
     */
    public function findTotalDistanceUsingDuration($exercises)
    {

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
                $totalSpeed = round(((60 * 60) / $paceToSpeed), 1);

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
                /**
                 * Step 1
                 * Lap 1 Duration in hour → 20 / 60 = 0.3333 (4 decimals place)
                 */
                $durationArray = explode(':', $exercise['duration']);

                $totalDuration = round(
                    (int) $durationArray[0]
                        + ((int) $durationArray[1] / 60)
                        + ((int) $durationArray[2] / 3600),

                    4
                );

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
             */ // Remove it and show the right duration and pace ...

            return array_sum($distanceByDurationSpeed);
        } else if (isset($distanceByDurationPace)) {
            /** PACE && DURATION
             * Step 5 – Find Total Distance for all Laps
             * Total Distance → 3.3 + 4.2 = 7.5 km (SUM OF ALL STEP 4)
             */
            return array_sum($distanceByDurationPace);
        }
    }
}
