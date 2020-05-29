<?php

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use App\Http\Controllers\Controller;
use App\Supports\SummaryCalculationTrait;

class CycleCalculationsController extends Controller
{
    use SummaryCalculationTrait;

    protected $total_distance_unit;
    protected $avg_speed_unit;
    protected $total_power_unit;
    protected $total_relative_power_unit;

    public function __construct()
    {
        $this->total_distance_unit = "km";
        $this->avg_speed_unit = "km/hr";
        $this->total_power_unit = "W";
        $this->total_relative_power_unit = "W/kg";
    }

    /**
     * CYCLE ( IN | OUT )
     */
    public function generateCalculation($trainingLog, $activityCode)
    {
        // MAIN
        $response = [];

        /** check user choose is duration or distance from log exercise */
        $isDuration = $trainingLog['exercise'][0]['duration'];

        # START Total Distance 
        $calculateTotalDistance = $this->calculateTotalDistance(
            $trainingLog,
            $activityCode,
            $isDuration
        );
        $response = array_merge($response, $calculateTotalDistance);
        # END Total Distance 

        # Lvl  
        $calculateLVL = $this->calculateLVL($trainingLog);
        $response = array_merge($response, $calculateLVL);

        # RPM  
        $calculateRPM = $this->calculateRPM($trainingLog);
        // dd('check rpm', $calculateRPM);
        $response = array_merge($response, $calculateRPM);

        # START Total Duration 
        $calculateDuration = $this->calculateDuration(
            $trainingLog,
            $isDuration
        );
        $response = array_merge($response, $calculateDuration);
        # END Total Duration 

        # Power (unit in W)
        $calculatePower = $this->calculatePower(
            $trainingLog,
            $response['total_lvl'],
            $response['total_rpm'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculatePower);

        # Relative Power (unit in W/kg)
        $calculateRelativePower = $this->calculateRelativePower(
            $response['total_power'],
            $trainingLog['user_detail']['weight']
        );
        $response = array_merge($response, $calculateRelativePower);

        # START Average Speed (can be either km/hr OR mile/hr, depending on the unit setting) 
        $calculateAverageSpeed = $this->calculateAverageSpeed(
            $trainingLog,
            $response['total_distance'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateAverageSpeed);
        # END Average Speed

        # Total kcal Burnt 
        $response['BMI'] = $BMI = round(((($trainingLog['user_detail']['weight'] ?? 0) / ($trainingLog['user_detail']['height'] ?? 0)  / ($trainingLog['user_detail']['height'] ?? 0)) * 10000), 2);
        $calculateTotalKcalBurn = $this->calculateTotalKcalBurn(
            $trainingLog,
            $response['total_power'],
            $response['BMI'],
            $response['total_lvl'],
            $response['total_rpm'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateTotalKcalBurn);

        return $response;
        // MAIN
    }

    /**
     * Calculate Total Duration condition wise
     */
    public function calculateTotalDistance($trainingLog, $activityCode, $isDuration)
    {
        $total_distance = 0;
        // "Total Duration" Start Calculate -------------------------------------------
        #  "A" Calculate
        if ($activityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            // NOTE - Remain To Test
            $total_distance = $this->getTotalDistanceFromStartEndLatitudeLongitude($trainingLog);
            $total_distance_code = "A";
        }

        # "B" | "C" Calculate 
        if ($total_distance == 0) {
            /** calculate from App Side Using Watch "B" */
            // NOTE - Remain To Test
            $exerciseTotalDistanceArray = collect($trainingLog['exercise'])->whereNotIn('total_distance',  ['0', null, 0])->pluck('total_distance')->all();
            if (isset($exerciseTotalDistanceArray) && is_array($exerciseTotalDistanceArray) && count($exerciseTotalDistanceArray) > 0) {
                /** all meter data convert to KM */
                $total_distance = array_first($exerciseTotalDistanceArray);
                $total_distance = round(($total_distance * 0.001), 2);
                $total_distance_code = "B";
            }
        }

        # "D" Calculate if  user choose duration in log  exercise or "E"
        // dd('check duration', $isDuration, $trainingLog['exercise']);
        if (isset($isDuration)) {
            // FIXME Remaining Testing "D"
            $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
            $total_distance_code = "D";
        } else {
            # E Calculate
            $total_distance = collect($trainingLog['exercise'])->sum('distance');
            $total_distance_code = "E";
        }
        // "Total Duration" End Calculate  -------------------------------------------
        return [
            'total_distance' => round($total_distance, 2),
            'total_distance_unit' =>  $this->total_distance_unit,
            'total_distance_code' => $total_distance_code
        ];
    }

    public function calculateLVL($trainingLog)
    {
        # code...
        $total_lvl = 0;
        # A) Record the Lvl value recorded from the power meter (if available). 

        # B 
        if (isset($trainingLog['exercise'][0]['lvl'])) {
            if (count($trainingLog['exercise']) == 1) {
                $total_lvl = $trainingLog['exercise'][0]['lvl'];
                $total_lvl_code = "B1";
            } else {
                $total_lvl = collect($trainingLog['exercise'])->avg('lvl');
                $total_lvl_code = "B2";
            }
        }

        // dd('total lvl ', $total_lvl, $trainingLog['exercise']);
        return [
            'total_lvl' => round($total_lvl, 2),
            'total_lvl_code' => $total_lvl_code ?? '',
        ];
    }


    public function calculateRPM($trainingLog)
    {
        $total_rpm = 0;
        # A) Record the RPM value recorded from the power meter (if available).

        # B 
        if (count($trainingLog['exercise']) == 1) {
            $total_rpm = $trainingLog['exercise'][0]['rpm'];
            $total_rpm_code = "B1";
        } else {
            $total_rpm = collect($trainingLog['exercise'])->avg('rpm');
            $total_rpm_code = "B2";
        }
        // dd('total rpm ', $total_rpm, $trainingLog['exercise']);
        return [
            'total_rpm' => round($total_rpm, 2),
            'total_rpm_code' => $total_rpm_code,
        ];
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

        # B) Record the Total Duration value recorded from the power meter (if available).  
        # NOTE Remaining from Device side
        // $totalDurationMinuteCode = "B";

        # C) Record the Total Duration recorded from the exercise watch (if available). 
        # NOTE Remaining from Device side
        // $totalDurationMinuteCode = "C";

        // (If user use ‘Duration’ in the log). 
        // dd('asd', $isDuration, $totalDurationMinute, $trainingLog['exercise']);
        if (
            isset($isDuration) &&
            in_array(($totalDurationMinute * 60),
                range(TRAINING_WORKOUT_STOP_IMMEDIATELY_MIN_SECOND, TRAINING_WORKOUT_STOP_IMMEDIATELY_MAX_SECOND)
            )
        ) {
            # E) If the user starts and ends the log immediately (too short to track), use the duration that 
            // is keyed on the log and add it all together (If user use ‘Duration’ in the log).
            # E Use here | all add it together duration 
            $totalDurationMinute = $this->addAllDurationTimeFromExercise($trainingLog['exercise']);
            // $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60)));

            $totalDurationMinuteCode = "E";
        } else if (
            !isset($isDuration) &&
            in_array(($totalDurationMinute * 60),
                range(TRAINING_WORKOUT_STOP_IMMEDIATELY_MIN_SECOND, TRAINING_WORKOUT_STOP_IMMEDIATELY_MAX_SECOND)
            )
        ) {
            # D) Use equation (If user use ‘Distance’ in the log. Please see "Duration Calculation Guide"). 
            $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
            // $response['total_duration'] = (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60)));
            $totalDurationMinuteCode = "D";
            // dd('final is ', $totalDurationMinute, $response['total_duration']); 
        }

        // dd('total rpm ', $total_rpm, $trainingLog['exercise']);
        return [
            'total_duration_minutes' => round($totalDurationMinute, 2),
            'total_duration' => (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60))),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
    }

    public function calculatePower($trainingLog, $total_lvl, $total_rpm, $total_duration_minutes)
    {
        $total_power = 0;

        # A) Record the Watt value recorded from the power meter.  

        # B) Take an average Watt value keyed in the training log (similar to calculation ‘Lvl’. Please 
        # refer to ‘Lvl’ section for the calculation) (if user use ‘Watt’ in the training log). 
        /** check is watt in exercise */
        $isWatt = $trainingLog['exercise'][0]['watt'];
        // dd('exercise', $isWatt,  $trainingLog);
        if (isset($total_power, $isWatt) && $total_power == 0) {
            // if user use ‘Watt’ in the training log). 
            $total_power = collect($trainingLog['exercise'])->avg('watt');
            $total_power_code = "B";
        } else {
            # (if user use ‘RPM’ in the training log).
            # First Calculate "LVL" and "RPM" then calculate this

            // Step 1) Work done = (Lvl* x (RPM x 6.12)) / 6
            $total_lvl_new = $total_lvl == 0 ? 1 : $total_lvl;
            $work_done = ($total_lvl_new *  ($total_rpm * 6.12)) / 6;
            // dd('check in', $work_done, $total_lvl, $total_lvl_new);

            // Step 2) Power = Total Duration x Work done (step 1)
            $total_power = $total_duration_minutes * $work_done;
            $total_power_code = "C";
        }

        return [
            'total_power'        =>     round($total_power, 2),
            'total_power_unit'   =>     $this->total_power_unit,
            'total_power_code'   =>     $total_power_code
        ];
    }

    public function calculateRelativePower($total_power, $userBodyWeight)
    {
        $total_relative_power = 0;

        # A) Record the relative power Watt value recorded from the power meter.    
        // TODO Remain from Device Side
        $total_relative_power_code = "A";

        # B) Use this equation to find Relative Power: 
        // Relative Power = Power / Body weight (in kg)
        $total_relative_power = $total_power / ($userBodyWeight ?? 0);
        $total_relative_power_code = "B";

        return [
            'total_relative_power'        =>     round($total_relative_power, 2),
            'total_relative_power_unit'   =>    $this->total_relative_power_unit,
            'total_relative_power_code'   =>     $total_relative_power_code
        ];
    }

    public function calculateAverageSpeed($trainingLog, $total_distance, $total_duration_minutes)
    {
        $avg_speed = 0;

        $trainingActivityCode = $trainingLog['training_activity']['code'];

        # B) Record the Average Speed value recorded from the power meter (if available). 
        $avg_speed_code = "B";

        # C) Record the Average Speed value recorded from the exercise watch (if available) 
        $avg_speed_code = "C";

        # D) Use equation (Please see Average Speed Calculation Guide). 
        $avg_speed_code = "D";
        $avg_speed = $this->calculateAverageSpeedGuide($trainingLog['exercise'], $total_distance, $total_duration_minutes);

        # A) Use phone GPS/tracker and apply this equation:
        // Average Speed = Total Distance (in km) / Total Duration (convert to minutes) (only for Outdoor) 
        if ($trainingActivityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            $avg_speed = $total_distance / $total_duration_minutes;
            // dd('check in or out',  $avg_speed, $total_distance, $total_duration_minutes);
            $avg_speed_code = "A";
        }
        // dd('check in or out', $trainingActivityCode,         $total_distance, $total_duration_minutes);

        return [
            'avg_speed'        =>     round($avg_speed, 2),
            'avg_speed_unit'    =>   $this->avg_speed_unit,
            'avg_speed_code'   =>     $avg_speed_code
        ];
    }

    public function calculateTotalKcalBurn($trainingLog, $power, $BMI,  $total_lvl,  $total_rpm, $total_duration_minutes)
    {
        // dd('log', $trainingLog);

        $isWatt = $trainingLog['exercise'][0]['watt'];

        if (isset($isWatt)) {
            /**
             * Step 1 (Find VO2): 
             * If user is using Watt in the training log: 
             * VO2 = 1.8 x ((power x 6) / body mass) + 7
             */
            $VO2 = 1.8 * (($power * 6) / $BMI) + 7;
        } else {
            /**
             * If user is using RPM in the training log: 
             * VO2 = 1.8 x ((*Lvl x 6.12 x RPM)/ body mass) + 7 
             */
            $tot_lvl = $total_lvl == 0 ? 1 : $total_lvl;
            $VO2 = 1.8 * ((($tot_lvl) * 6.12 * $total_rpm) / $BMI) + 7;
        }
        $VO2 = round($VO2, 2);
        /**
         * Step 2 (Find MET value): 
         * MET = VO2/ 3.5 
         */
        $MET = round(($VO2 / 3.5), 2);

        /**
         * Step 3 (Find kcal/min): 
         * kcal/min = (MET x 3.5 x body mass in kg) / 200 
         */
        $kcal_min = round((($MET * 3.5 * $BMI) / 200), 2);

        /**
         * Step 4 (Final total kcal): 
         * Total kcal = (kcal/min) x total duration in mins
         */
        $total_kcal = round((($kcal_min) * $total_duration_minutes), 2);
        // dd(
        //     '"Calc found',
        //     "total_kcal " . $total_kcal,
        //     "total_duration_minutes " . $total_duration_minutes,
        //     "BMI " . $BMI,
        //     "MET " . $MET,
        //     "VO2 " . $VO2,
        //     "tot_lvl " . $tot_lvl,
        //     "power " . $power

        // );
        return [
            "total_kcal"    =>  round($total_kcal, 2)
        ];
    }
}
