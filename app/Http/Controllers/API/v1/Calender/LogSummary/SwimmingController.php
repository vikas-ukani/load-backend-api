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
        $this->avg_speed_unit = "km/hr";
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
        $calculateTotalDistance = $this->calculateTotalDistance(
            $trainingLog,
            $isDuration
        );
        $response = array_merge($response, $calculateTotalDistance);
        // dd('check return data', $response);

        # 2. Total Duration
        $calculateDuration = $this->calculateDuration(
            $trainingLog,
            $isDuration
        );
        $response = array_merge($response, $calculateDuration);

        # 3. Average Pace 
        $calculateAvgPace = $this->calculateAvgPace(
            $trainingLog['exercise'],
            $response['total_distance'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateAvgPace);

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
    public function calculateTotalDistance($trainingLog, $isDuration)
    {
        $total_distance = 0;
        // "Total Duration" Start Calculate -------------------------------------------
        #  "A" Calculate
        $total_distance = collect($trainingLog['exercise'])->where('total_distance', null)->pluck('total_distance')->last();
        $total_distance_code = "A";

        if (isset($isDuration)) {
            $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
            $total_distance_code = "B";
        } else {
            # E Calculate
            $total_distance = collect($trainingLog['exercise'])->sum('distance');
            $total_distance_code = "C";
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
            # B) If the user starts and ends the log immediately (too short to track), use the duration that
            // is keyed on the log and add it all together (If user use ‘Duration’ in the log).
            # B Use here | all add it together duration 
            $totalDurationMinute = $this->addAllDurationTimeFromExercise($trainingLog['exercise']);
            // $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60)));
            $totalDurationMinuteCode = "B";
        } else if (!isset($isDuration) && $totalDurationMinute == 0) {
            # C) Use equation (If user use ‘Distance’ in the log. Please see "Duration Calculation Guide"). 
            $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
            // $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60)));
            $totalDurationMinuteCode = "C";
            // dd('final is ', $totalDurationMinute, $response['total_duration']);
        }

        // dd('total rpm ', $total_rpm, $trainingLog['exercise']);
        return [
            'total_duration_minutes' => round($totalDurationMinute, 2),
            'total_duration' => (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60))),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
    }


    public function calculateAvgPace($exercises, $totalDistance, $totalDurationMinute)
    {
        $avg_pace = 0;
        #  "A" Calculate
        $avg_pace = collect($exercises)->where('avg_pace', null)->pluck('avg_pace')->last();
        $avg_pace_code = "A";

        if ($avg_pace == 0) {
            $avg_pace = $this->calculatePaceCalculationGuid($exercises, $totalDistance, $totalDurationMinute);
            $avg_pace_code = "B";
        }

        // "avg pace" End Calculate  -------------------------------------------
        return [
            'avg_pace' => round($avg_pace, 2),
            'avg_pace_unit' =>  $this->avg_pace_unit,
            'avg_pace_code' => $avg_pace_code
        ];
    }


    public function calculateTotalKcal($trainingLog, $totalDurationMinute)
    {
        $total_kcal_burn = 0;

        # A) Record the Total kcal Burnt value recorded from the exercise watch (if available).
        $total_kcal_burn = collect($trainingLog['exercise'])->where('total_kcal', null)->pluck('total_kcal')->last();
        $total_kcal_burn_code = "A";

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
