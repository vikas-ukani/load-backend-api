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

    /**
     * generateCalculation
     *
     * @param  mixed $trainingLog
     * @param  mixed $activityCode
     * @return void
     */
    public function generateCalculation($trainingLog, $activityCode)
    {
        // MAIN
        $response = [];

        /** check user choose is duration or distance from log exercise */
        $isDuration = $trainingLog['exercise'][0]['duration'];

        # Total Duration
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

        # Total Distance 
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

        # Average Pace 
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

        # Average Speed
        $calculateAverageSpeed = $this->calculateAverageSpeedPace(
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
     * calculateDuration
     *
     * @param  mixed $trainingLog
     * @param  mixed $isDuration
     * @return void
     */
    public function calculateDuration($trainingLog, $isDuration)
    {
        # A, B → C OR D

        $totalDurationMinute = 0;

        # A) If the user click on the ‘Start’ button, use phone tracker 
        # (when user starts the workout log to when the workout log ends).
        $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        $totalDurationMinuteCode = "A";

        if (!$isCompleteButton &&  $totalDurationMinute == 0) {
            # B) If the user click on the ‘Start’ button, 
            # record the Total Duration value recorded from the exercise watch (if available).
            # $totalDurationMinuteCode = "B";
        }

        # C) If the user click on the ‘Complete’ button to log the workout, use equation 
        # (If user use ‘Distance’ in the log. Please see Duration Calculation Guide).
        if ($isCompleteButton && $totalDurationMinute == 0 && !isset($isDuration)) {
            $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
            $totalDurationMinuteCode = "C";
        }

        # D) If the user click on the ‘Complete’ button to log the workout, use the duration 
        # (If user use ‘Duration’ in the log):
        # Add all the Duration (including Rest) data keyed in the log.
        if ($isCompleteButton && isset($isDuration) && $totalDurationMinute == 0) {
            $totalDurationMinute = $this->addAllDurationAndRestTimeFromExercise($trainingLog['exercise']);
            $totalDurationMinuteCode = "D";
        }
        return [
            'total_duration_minutes' => round($totalDurationMinute, 1),
            'total_duration' => $this->convertDurationMinutesToTimeFormat($totalDurationMinute),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
    }

    /** Calculate Total Duration condition wise
     * calculateTotalDistance
     * @param  mixed $trainingLog
     * @param  mixed $activityCode
     * @param  mixed $isDuration
     * @return void
     */
    public function calculateTotalDistance($trainingLog, $activityCode, $isDuration)
    {
        # A, B → C OR D
        $total_distance = 0;

        $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        # A) If the user click on the ‘Start’ button, 
        # use phone location and motion sensors (GPS + Accelerometer)
        if (!$isCompleteButton) {
            $total_distance = $this->getTotalDistanceFromStartEndLatitudeLongitude($trainingLog);
            $total_distance_code = "A";
        }

        $lastExerciseTotalDistance = collect($trainingLog['exercise'])->whereNotIn('total_distance', ['0', 0, null])->pluck('total_distance')->first();
        if (!$isCompleteButton && $total_distance == 0 && isset($lastExerciseTotalDistance)) {
            # B) If the user click on the ‘Start’ button, 
            # record the Total Distance value recorded from the exercise watch (if available).
            $total_distance = $this->getDistanceFromExerciseWatch($lastExerciseTotalDistance); // function in summary controller
            $total_distance_code = "B";
        }

        if ($isCompleteButton && isset($isDuration) && $total_distance == 0) {
            # C) If the user click on the ‘Complete’ button to log the workout, use equation 
            # (If user use ‘Duration’ in the log. Please see Distance Calculation Guide).
            $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
            $total_distance_code = "C";
        } else if ($isCompleteButton) {
            # D) If the user click on the ‘Complete’ button to log the workout,
            # add all the distance keyed in the log together (If user use ‘Distance’ in the log).
            $total_distance = collect($trainingLog['exercise'])->sum('distance');
            $total_distance_code = "D";
        }
        return [
            'total_distance' =>  round($total_distance, 1),
            'total_distance_unit' =>  $this->total_distance_unit,
            'total_distance_code' => $total_distance_code
        ];
    }

    /**
     * calculateAverageSpeedPace
     *
     * @param  mixed $exercises
     * @param  mixed $avg_pace
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculateAverageSpeedPace($exercises, $avg_pace, $totalDistance, $totalDurationMinute)
    {
        # A, B → C
        $avg_speed = 0;

        $totalDurationMinute = $this->totalDurationMinute(['exercise' => $exercises]);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        # A) If the user click on the ‘Start’ button, use phone location and motion sensors (GPS + Accelerometer)
        if (!$isCompleteButton) {
            $avg_speed = $totalDistance / ($totalDurationMinute / 60); // minute to hr
            /** use this told by yash */
            // $avg_speed = $totalDistance / $totalDurationMinute;
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            // if (isset($avg_pace)) {
            //     # convert pace to speed
            //     $avg_speed = (60 / $avg_pace);
            // }
            $avg_speed_code = "A";
        }

        if (!$isCompleteButton && $avg_speed == 0) {
            # B) If the user click on the ‘Start’ button, 
            # record the Average Pace value recorded from the exercise watch (if available).
            // FIXME - Complete this..
            // $avg_speed_code = "B";
        }

        if ($isCompleteButton && $avg_speed == 0) {
            # C) If the user click on the ‘Complete’ button to log the workout, 
            # use equation (Please refer to Average Pace Calculation Guide_Swimming).
            $avg_pace = $this->calculatePaceCalculationGuidForSwimming($exercises, $totalDistance, $totalDurationMinute);
            // Convert Average Pace (mins/100m) to Average Speed (m/min) = 100 / Average Pace in fraction
            $avg_speed = 100 / $avg_pace;
            $avg_speed_code = "C";
        }

        return   [
            'avg_speed' => round($avg_speed, 1),
            'avg_speed_unit' => $this->avg_speed_unit,
            'avg_speed_code' => $avg_speed_code ?? ''
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
        # A, B → C OR D
        $avg_pace = 0;

        $totalDurationMinute = $this->totalDurationMinute(['exercise' => $exercises]);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        if (!$isCompleteButton) {
            # A) If the user click on the ‘Start’ button, 
            # use phone location and motion sensors (GPS +Accelerometer)
            // $avg_speed = $totalDistance / $totalDurationMinute;
            // $avg_pace = 60 / $avg_speed;
            $avg_pace = $totalDistance == 0 ? 0 : ($totalDurationMinute / $totalDistance);
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            $avg_pace_code = "A";
        }

        if (!$isCompleteButton && $avg_pace == 0) {
            # B) If the user click on the ‘Start’ button, 
            # record the Average Pace value recorded from the exercise watch (if available).
            // $avg_speed = $totalDistance / $totalDurationMinute;
            // $avg_pace = 60 / $avg_speed;
            $avg_pace = $totalDistance == 0 ? 0 : ($totalDurationMinute / $totalDistance);
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            $avg_pace_code = "B";
        }

        if ($isCompleteButton && $avg_pace == 0) {
            # C) If the user click on the ‘Complete’ button to log the workout, 
            # use equation (Please see Average Pace Calculation Guide_Swimming).
            $avg_pace = $this->calculatePaceCalculationGuidForSwimming($exercises, $totalDistance, $totalDurationMinute);
            $avg_pace_code = "C";
        }

        if (!$isCompleteButton && $avg_pace == 0) {
            # D) If the user click on the ‘Start’ button and if no movement detected, 
            # leave the value empty. Allow the user to key in the value manually (Step 5).
            $avg_pace = 0;
            $avg_pace_code = "D";
        }

        $avg_pace = $this->convertPaceNumberTo_M_S_format($avg_pace ?? 0);
        return [
            'avg_pace' => $avg_pace ?? null,
            'avg_pace_unit' =>  $this->avg_pace_unit,
            'avg_pace_code' => $avg_pace_code ?? ''
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

    /**
     * calculateTotalKcal
     *
     * @param  mixed $trainingLog
     * @param  mixed $totalDurationMinute
     * @return void
     */
    public function calculateTotalKcal($trainingLog, $totalDurationMinute)
    {
        # A → B
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
}
