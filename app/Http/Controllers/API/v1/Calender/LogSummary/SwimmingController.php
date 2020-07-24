<?php

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use App\Http\Controllers\Controller;
use App\Supports\SummaryCalculationTrait;

class SwimmingController extends Controller
{
    use SummaryCalculationTrait;

    protected $total_distance_unit;
    protected $avg_speed_unit;
    protected $avg_pace_unit;
    protected $total_power_unit;
    protected $total_relative_power_unit;

    public function __construct()
    {
        $this->total_distance_unit = "km";
        // $this->avg_speed_unit = "km/hr"; // OLD
        $this->avg_speed_unit = "m/min"; // NEW
        $this->avg_pace_unit = "min/100m";
        $this->total_power_unit = "W";
        $this->total_relative_power_unit = "W/kg";
    }

    public function generateCalculation($trainingLog, $activityCode)
    {
        // MAIN
        $response = [];

        /** check user choose is duration or distance from log exercise */
        $isDuration = $trainingLog['exercise'][0]['duration'];

        # 1. Total Distance 
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

        # 2. Total Duration
        /** get first from generated calculation */
        if (isset($trainingLog['generated_calculations'], $trainingLog['generated_calculations']['total_duration'])) {
            $response = array_merge($response, [
                'total_duration' => $trainingLog['generated_calculations']['total_duration'],
                'total_duration_minutes' => $this->convertDurationToMinutes($trainingLog['generated_calculations']['total_duration'])
            ]);
        } else {
            $calculateDuration = $this->calculateDuration(
                $trainingLog,
                $isDuration
            );
            $response = array_merge($response, $calculateDuration);
        }

        # 3. Average Pace 
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

        // dd('asd', $trainingLog);
        # Start Average Speed
        $calculateAverageSpeed = $this->calculateAverageSpeed(
            $trainingLog['exercise'],
            $response['avg_pace'],
            $response['total_distance'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateAverageSpeed);

        # Start Average Heart Rate
        $calculateAverageHeartRate = $this->calculateAverageHeartRate(
            $trainingLog['exercise']
        );
        $response = array_merge($response, $calculateAverageHeartRate);
        # End Average Heart Rate

        # 4. Total kcal Burn
        $calculateTotalKcal = $this->calculateTotalKcal(
            $trainingLog,
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateTotalKcal);
        return $response;
    }

    /**
     * Calculate Total Duration condition wise
     */
    public function calculateTotalDistance($trainingLog, $activityCode, $isDuration)
    {
        $total_distance = 0;

        # A) Use phone location and motion sensors (GPS + Accelerometer)
        // FIXME - Complete this.
        // $total_distance = collect($trainingLog['exercise'])->where('total_distance', null)->pluck('total_distance')->last();
        // $total_distance_code = "A";

        if ($total_distance == 0) {
            # B) Record the Total Distance value recorded from the exercise watch (if available).
            // FIXME - Find from exercise watch
            $total_distance_code = "B";
        }
        if (isset($isDuration) && $total_distance == 0) {
            # C) If the user click on the ‘Complete’ button to log the workout, use equation 
            # (If user use ‘Duration’ in the log. Please see Distance Calculation Guide).
            $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
            $total_distance_code = "C";
        } else {
            # D) If the user click on the ‘Complete’ button to log the workout,
            # add all the distance keyed in the log together (If user use ‘Distance’ in the log).
            $total_distance = collect($trainingLog['exercise'])->sum('distance');
            $total_distance_code = "D";
        }
        // "Total Duration" End Calculate  -------------------------------------------
        return [
            'total_distance' =>  round($total_distance, 2),
            'total_distance_unit' =>  $this->total_distance_unit,
            'total_distance_code' => $total_distance_code
        ];
    }

    public function calculateDuration($trainingLog, $isDuration)
    {
        // dd('duration', $trainingLog);
        $totalDurationMinute = 0;

        // No Needed
        // # A) Use phone tracker (when user starts the workout log to when the workout log ends). 
        // $start_time = collect($trainingLog['exercise'])->where('start_time', '<>', null)->pluck('start_time')->first();
        // $end_time = collect($trainingLog['exercise'])->where('end_time', '<>', null)->pluck('end_time')->first();
        // // dd('check is in start_time and end Time', $start_time, $end_time,   $trainingLog['exercise']);

        // if (isset($start_time, $end_time)) {
        //     /** Calculate Total Duration From Start Time To End Time From Exercises */
        //     $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        //     $totalDurationMinuteCode = "A";
        // }

        # C) Record the Total Duration recorded from the exercise |watch| (if available). 
        # NOTE Remaining from Device side
        // $totalDurationMinuteCode = "A";

        // (If user use ‘Duration’ in the log). 
        // dd('asd', $isDuration, $totalDurationMinute, $trainingLog['exercise']);
        if (
            isset($isDuration) &&
            in_array(($totalDurationMinute * 60),
                range(TRAINING_WORKOUT_STOP_IMMEDIATELY_MIN_SECOND, TRAINING_WORKOUT_STOP_IMMEDIATELY_MAX_SECOND)
            )
        ) {
            # B) Record the Total Duration value recorded from the exercise watch (if available).
            // FIXME - get from watch
        } else if (!isset($isDuration) && $totalDurationMinute == 0) {
            # C) Use equation (If user use ‘Distance’ in the log. Please see "Duration Calculation Guide"). 
            $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
            // $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60)));
            $totalDurationMinuteCode = "C";
            // dd('final is ', $totalDurationMinute, $response['total_duration']);
        }

        if (isset($isDuration) && $totalDurationMinute == 0) {
            $totalDurationMinute = $this->addAllDurationAndRestTimeFromExercise($trainingLog['exercise']);
            $totalDurationMinuteCode = "D";
        }
        // dd('total rpm ', $total_rpm, $trainingLog['exercise']);
        return [
            'total_duration_minutes' => round($totalDurationMinute, 2),
            'total_duration' => $this->convertDurationMinutesToTimeFormat($totalDurationMinute),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
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
    public function calculateAvgPace($exercises, $totalDistance, $totalDurationMinute, $activityCode)
    {
        $avg_pace = 0;
        # A) Use phone location and motion sensors (GPS + Accelerometer)
        //  FIXME - Calculate it 
        // $avg_pace_code = "A";

        if ($avg_pace == 0) {
            # B) Record the Average Pace value recorded from the exercise watch (if available).
            $avg_pace = collect($exercises)->where('avg_pace', null)->pluck('avg_pace')->last();
            $avg_pace_code = "B";
        }

        if ($avg_pace == 0) {
            # C) If the user click on the ‘Complete’ button to log the workout, 
            # use equation (Please see Average Pace Calculation Guide_Swimming).
            $avg_pace = $this->calculatePaceCalculationGuidForSwimming($exercises, $totalDistance, $totalDurationMinute);
            $avg_pace_code = "C";
        }
        $avg_pace = $this->convertPaceNumberTo_M_S_format($avg_pace);
        return [
            'avg_pace' => $avg_pace ?? null,
            'avg_pace_unit' =>  $this->avg_pace_unit,
            'avg_pace_code' => $avg_pace_code
        ];
    }
    
    /**
     * calculateAverageSpeed
     *
     * @param  mixed $exercises
     * @param  mixed $avg_pace
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculateAverageSpeed($exercises, $avg_pace, $totalDistance, $totalDurationMinute)
    {
        $avg_speed = 0;

        # A) Use phone location and motion sensors (GPS + Accelerometer)
        // FIXME - Complete this..

        if ($avg_speed == 0) {
            # B) Record the Average Pace value recorded from the exercise watch (if available).
            // $avg_speed =
            // $avg_speed_code = "B";
        }

        if ($avg_speed == 0) {
            # C) If the user click on the ‘Complete’ button to log the workout,
            # use equation (Please refer to Average Pace Calculation Guide_Swimming).
            // Convert Average Pace (mins/100m) to Average Speed (m/min) = 100 / Average Pace in fraction
            $avg_pace = $this->calculatePaceCalculationGuidForSwimming($exercises, $totalDistance, $totalDurationMinute);
            $avg_speed = 100 / $avg_pace;
            $avg_speed_code = "C";
        }

        return   [
            'avg_speed' => round($avg_speed, 2),
            'avg_speed_unit' => $this->avg_speed_unit,
            'avg_speed_code' => $avg_speed_code
        ];
    }
    
    /**
     * calculateTotalKcal
     *
     * @param  mixed $trainingLog
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculateTotalKcal($trainingLog, $totalDurationMinute)
    {
        $total_kcal_burn = 0;

        # A) Record the Total kcal value recorded from the exercise watch (if available).
        // $total_kcal_burn = collect($trainingLog['exercise'])->where('total_kcal', null)->pluck('total_kcal')->last();
        // $total_kcal_burn_code = "A";

        # B) Use this method and equation:
        if ($total_kcal_burn == 0) {
            # Step 1) kcal/min = 0.0175 x MET x user’s weight (in kilograms)
            $userWright = $trainingLog['user_detail']['weight'] ?? 0;
            $MET = $trainingLog['training_log_style']['mets'];
            $kcal_min = round((0.0175 * $MET * $userWright), 2);

            # Step 2) Total kcal = (kcal/min) x total exercise duration in mins
            $total_kcal_burn = $kcal_min * $totalDurationMinute;
            $total_kcal_burn_code =  "B";
        }
        return [
            'kcal_min' => $kcal_min ?? null,
            'total_kcal' => round($total_kcal_burn, 2),
            'total_kcal_code' => $total_kcal_burn_code,
        ];
    }
    
    /**
     * calculateAverageHeartRate
     *
     * @param  mixed $exercises
     * @return void
     */
    public function calculateAverageHeartRate($exercises)
    {
        $avg_heart_rate = 0;
        # A) Record the Average Heart Rate value recorded from the exercise watch (if available).
        // FIXME - Complete it .
        # B) If user is not using any third party heart rate monitor, Average Heart Rate will show ‘-’.
        // else show dash "-"

        return   [
            'avg_heart_rate' => round($avg_heart_rate, 2),
            // 'avg_heart_rate_unit' => $this->avg_heart_rate_unit,
            // 'avg_heart_rate_code' => $avg_heart_rate_code
        ];
    }
}
