<?php

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Supports\SummaryCalculationTrait;
use Illuminate\Support\Arr;

class ResistanceCalculationController extends Controller
{
    use SummaryCalculationTrait;


    protected $total_volume_unit;
    protected $average_weight_lifted_unit;

    public function __construct()
    {
        $this->total_volume_unit = "kg";
        $this->average_weight_lifted_unit = "kg";
    }
    /**
     * How to get Average Weight Lifted:
     * Total Volume / All the reps in the training log.
     */


    /**
     * CYCLE ( IN | OUT )
     */
    public function generateCalculation($trainingLog, $activityCode)
    {
        // START MAIN
        $response = [];
        $isDuration = $trainingLog['exercise'][0]['duration'];

        # START Total Duration 
        $calculateDuration = $this->calculateDuration(
            $trainingLog,
            $isDuration
        );
        $response = array_merge($response, $calculateDuration);
        # END Total Duration 
        # A) Use phone tracker (when user starts the workout log to when the workout log ends). 
        // $start_time = collect($trainingLog['exercise'])->where('start_time', '<>', null)->pluck('start_time')->first();
        // $end_time = collect($trainingLog['exercise'])->where('end_time', '<>', null)->pluck('end_time')->first();
        // dd('check is in start_time and end Time', $start_time, $end_time,   $trainingLog['exercise']);

        // if (isset($start_time, $end_time)) {
        //     /** Calculate Total Duration From Start Time To End Time From Exercises */
        //     $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        //     $totalDurationMinuteCode  = "A";
        //     $response['total_duration_minutes'] = round($totalDurationMinute, 2);
        //     $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60)));
        //     $response['total_duration_code'] = $totalDurationMinuteCode;
        // }
        /** 
         * How to get Total Volume: 
         * Find Volume (for each set) = Weight x Reps
         * Add all the Volume of all the sets in each exercise.
         */
        $allVolume = [];
        $allRepsCount = 0;
        foreach ($trainingLog['exercise'] as $key => $exercises) {
            /** before any change please look at the exercises object */
            foreach ($exercises['data'] as $key => $exercise) {
                $allVolume[] = round((((float) $exercise['weight']) * ((float) $exercise['reps'])), 2);
                $allRepsCount += (float) $exercise['reps'];
            }
        }
        // wrong calculation applied here,
        $response['total_volume'] = array_sum($allVolume);
        $response['total_volume_unit'] = $this->total_volume_unit;

        /** maintain reminder here */
        $response['average_weight_lifted'] = round(($response['total_volume'] / $allRepsCount), 2);
        $response['average_weight_lifted_unit'] = $this->average_weight_lifted_unit;

        // dd('res', $response);
        return $response;
        // END MAIN
    }

    public function calculateDuration($trainingLog, $isDuration)
    {
        // dd('duration', $trainingLog);
        $totalDurationMinute = 0;

        # A) Use phone tracker (when user starts the workout log to when the workout log ends). 
        $start_time = collect($trainingLog['exercise'])->where('start_time', '<>', null)->pluck('start_time')->first();
        $end_time = collect($trainingLog['exercise'])->where('end_time', '<>', null)->pluck('end_time')->first();
        // dd('check is in start_time and end Time', $start_time, $end_time,   $trainingLog['exercise']);
        if (isset($start_time, $end_time)) {
            /** Calculate Total Duration From Start Time To End Time From Exercises */
            $totalDurationMinute = $this->totalDurationMinute($trainingLog);
            $totalDurationMinuteCode = "A";
        }
        // dd('total rpm ', $total_rpm, $trainingLog['exercise']);
        return [
            'total_duration_minutes' => round($totalDurationMinute, 2),
            'total_duration' => $this->convertDurationMinutesToTimeFormat($totalDurationMinute),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
    }



    /** used from Generate Calculation  - TrainingLog.php */
    public function calculateAvgPace($exercises, $totalDistance, $totalDurationMinute, $activityCode)
    {
        $avg_pace = 0;
        // $avg_pace = round($avg_pace, 2);
        // $avg_pace = implode(':', explode('.', $avg_pace));
        $avg_pace = $this->convertPaceNumberTo_M_S_format($avg_pace);

        // "avg pace" End Calculate  -------------------------------------------
        return [
            'avg_pace' => $avg_pace ?? null,
            'avg_pace_unit' =>  $this->avg_pace_unit ?? null,
            'avg_pace_code' => $avg_pace_code ?? null
        ];
    }
}
