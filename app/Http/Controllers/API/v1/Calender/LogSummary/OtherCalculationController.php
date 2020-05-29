<?php

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use App\Http\Controllers\Controller;
use App\Supports\SummaryCalculationTrait;

class OtherCalculationController extends Controller
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

    /**
     * CYCLE ( IN | OUT )
     */
    public function generateCalculation($trainingLog, $activityCode)
    {
        // START MAIN
        $response = [];
        /** check user choose is duration or distance from log exercise */
        $isDuration = $trainingLog['exercise'][0]['duration'];

        # 1. Total Distance 
        $calculateTotalDistance = $this->calculateTotalDistance(
            $trainingLog,
            $isDuration
        );
        $response = array_merge($response, $calculateTotalDistance);
        # 1. Total Distance END

        # 2. Lvl  
        $calculateLVL = $this->calculateLVL($trainingLog);
        $response = array_merge($response, $calculateLVL);
        # 2. Lvl  END

        # 3. RPM  
        $calculateRPM = $this->calculateRPM($trainingLog);
        $response = array_merge($response, $calculateRPM);
        # 3. RPM  END

        # 4. Total Duration 
        $calculateDuration = $this->calculateDuration(
            $trainingLog,
            $isDuration
        );
        $response = array_merge($response, $calculateDuration);
        # 4. Total Duration END

        # 5. Power
        $calculatePower = $this->calculatePower(
            $trainingLog,
            $response['total_lvl'],
            $response['total_rpm'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculatePower);
        # 5. Power END

        # 6. Relative Power (unit in W/kg)
        $calculateRelativePower = $this->calculateRelativePower(
            $response['total_power'],
            $trainingLog['user_detail']['weight']
        );
        $response = array_merge($response, $calculateRelativePower);
        # 6. Relative Power (unit in W/kg) END

        # 7. Average Speed (can be either km/hr OR mile/hr, depending on the unit setting)
        $calculateAverageSpeed = $this->calculateAverageSpeed(
            $trainingLog,
            $response['total_distance'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateAverageSpeed);
        # 7. Average Speed END

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
        # Total kcal Burnt END 

        return $response;
        // END MAIN
    }

    /**
     * Calculate Total Duration condition wise
     */
    public function calculateTotalDistance($trainingLog, $isDuration)
    {
        $total_distance = 0;
        // "Total Duration" Start Calculate -------------------------------------------
        #  "A") Use the value recorded from the exercise watch (if available)
        $total_distance = collect($trainingLog['exercise'])->where('total_distance', null)->pluck('total_distance')->last();
        $total_distance_code = "A";


        # "B" If the user click on the ‘Complete’ button to log the workout, use equation 
        // (If user use ‘Duration’ in the log. Please see Distance Calculation Guide).
        if (isset($isDuration)) {
            $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
            $total_distance_code = "B";
        } else {
            # "C" If the user click on the ‘Complete’ button to log the workout, add all the distance keyed in
            // the log together (If user use ‘Distance’ in the log).
            $total_distance = collect($trainingLog['exercise'])->sum('distance');
            $total_distance_code = "C";
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
        $lvlResponse = [];
        $keyName = 'lvl';
        $codeName = 'A';
        # "A" If there is only 1 lap recorded in the training log, just use the value that the user set.
        $lvlResponse = $this->getAverageOrFirst($trainingLog['exercise'], $keyName, $codeName);
        // dd('total lvl ', $total_lvl, $trainingLog['exercise']);
        return [
            'total_lvl' => round($lvlResponse[$keyName], 2),
            'total_lvl_code' => $lvlResponse[$codeName],
        ];
    }


    public function calculateRPM($trainingLog)
    {
        $total_rpm = 0;
        # "A" Calculation method is the same as ‘Lvl’. Please refer to Lvl section.
        $keyName = 'rpm';
        $codeName = 'A';
        # "A" If there is only 1 lap recorded in the training log, just use the value that the user set.
        $rpmResponse = $this->getAverageOrFirst($trainingLog['exercise'], $keyName, $codeName);
        $total_rpm = $rpmResponse[$keyName];
        $total_rpm_code = $rpmResponse[$codeName];

        // if (count($trainingLog['exercise']) == 1) {
        //     $total_rpm = $trainingLog['exercise'][0]['rpm'];
        //     $total_rpm_code = "B1";
        // } else {
        //     $total_rpm = collect($trainingLog['exercise'])->avg('rpm');
        //     $total_rpm_code = "B2";
        // }

        return [
            'total_rpm' => round($total_rpm, 2),
            'total_rpm_code' => $total_rpm_code,
        ];
    }

    public function calculateDuration($trainingLog, $isDuration)
    {
        $totalDurationMinute = 0;

        $start_time = collect($trainingLog['exercise'])->where('start_time', '<>', null)->pluck('start_time')->first();
        $end_time = collect($trainingLog['exercise'])->where('end_time', '<>', null)->pluck('end_time')->first();

        # "B" Record the Total Duration recorded from the exercise watch (if available).
        $totalDurationMinuteCode = "B";


        // (If user use ‘Duration’ in the log). 
        // dd('asd', $isDuration, $totalDurationMinute, $trainingLog['exercise']);
        if (!isset($isDuration)) {
            // dd('asd', $isDuration);
            // && in_array(($totalDurationMinute * 60), range(0, 10)) // not mention for this condition
            # "A" Use phone tracker (when user starts the workout log to when the workout log ends) and
            # apply the equation accordingly (If user use ‘Distance’ in the log. Please see Duration
            # Calculation Guide).
            $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
            $totalDurationMinuteCode = "A";

            /** if found 0 then add all start and end duration Time */
            if ($totalDurationMinute == 0) {
                $totalDurationMinute = $this->totalDurationMinute($trainingLog);
                $totalDurationMinuteCode = 'A2';
            }
            // dd('final duration', $totalDurationMinute, (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60))),  $totalDurationMinuteCode, $trainingLog['exercise']);
        } else if (isset($isDuration)) {
            // && in_array(($totalDurationMinute * 60), range(0, 10)) // not mention for this condition
            # "C" If the user click on the ‘Complete’ button to log the workout, use the duration that is
            # keyed on the log (including ‘Rest’ duration) and add it all together (If user use ‘Duration’
            # in the log).
            $getAllRest = collect($trainingLog['exercise'])->pluck('rest')->all();
            $getAllDuration = collect($trainingLog['exercise'])->pluck('duration')->all();

            /** add all it together duration from lap */
            $totalDurationMinute = $this->addAllDurationTimeFromExercise($trainingLog['exercise']);

            /** add REST in duration in minutes */
            foreach ($trainingLog['exercise'] as $key => $exercise) {
                if (isset($exercise['rest'])) {
                    $restArray = explode(":", $exercise['rest']);
                    $totalDurationMinute += (
                        ((int) $restArray[0])  // Minutes and second only format is  00:00
                        + (((int) $restArray[1] / 60) ?? 0) // second to minutes
                    );
                }
            }
            $totalDurationMinuteCode = "C";
        }
        return [
            'total_duration_minutes' => round($totalDurationMinute, 2),
            'total_duration' => (gmdate("H:i:s", (($totalDurationMinute ?? 0)  * 60))),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
    }


    public function getAverageOrFirst($exercises, $keyName, $codeName)
    {
        if (count($exercises) == 1) {
            $total_lvl = $exercises[0][$keyName];
            $total_lvl_code = $codeName . "1";
        } else if (count($exercises) > 1) {
            $total_lvl = collect($exercises)->avg($keyName);
            $total_lvl_code = $codeName . "2";
        }
        return [
            $keyName => $total_lvl,
            $codeName => $total_lvl_code,
        ];
    }

    public function calculatePower($trainingLog, $total_lvl, $total_rpm, $total_duration_minutes)
    {
        $total_power = 0;
        /** check user which column has selected */
        $isWatt = $trainingLog['exercise'][0]['watt'];

        # "A" Take an average Watt value keyed in the training log 
        // (similar to calculation ‘Lvl’. Please refer to ‘Lvl’ section for the calculation) 
        // (if user use ‘Watt’ in the training log).
        if (isset($isWatt)) {
            $keyName = 'watt';
            $codeName = 'A';
            /** if more then one lap then average else direct take from first lap */
            $wattResponse = $this->getAverageOrFirst($trainingLog['exercise'], $keyName, $codeName);
            $total_power = $wattResponse[$keyName];
            $total_power_code = $wattResponse[$codeName];
            // if (count($trainingLog['exercise']) > 1) {
            //     $total_power = collect($trainingLog['exercise'])->avg('watt');
            //     $total_power_code = "A";
            // } else if (count($trainingLog['exercise']) == 1) {
            //     $total_power = $trainingLog['exercise']['watt'];
            //     $total_power_code = "A";
            // }
        } else {
            # "B" Use RPM & Lvl values and apply the equation 
            // (if user use ‘RPM’ in the training log):
            // (Refer to the RPM and Lvl sections for the calculation)

            # Step 1) Work done = (Lvl* x (RPM x 6.12)) / 6
            # *If user set ‘0’ for Lvl in the training log, use ‘1’ in the equation by default.
            $total_lvl_new = $total_lvl == 0 ? 1 : $total_lvl;
            $work_done = ($total_lvl_new * ($total_rpm * 6.12)) / 6;

            # Step 2) Power = Total Duration x Work done (step 1)
            $total_power = $total_duration_minutes * $work_done;

            //  REVIEW - Remaining from here.
            $total_power_code = "B";
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
        $total_relative_power_code = "A";

        # "A" Use this equation to find Relative Power: 
        // Relative Power = Power / Body weight (in kg)
        $total_relative_power = $total_power / ($userBodyWeight ?? 0);
        $total_relative_power_code = "A";

        return [
            'total_relative_power'        =>     round($total_relative_power, 2),
            'total_relative_power_unit'   =>    $this->total_relative_power_unit,
            'total_relative_power_code'   =>     $total_relative_power_code
        ];
    }

    public function calculateAverageSpeed($trainingLog, $total_distance, $total_duration_minutes)
    {
        $avg_speed = 0;
        # "A" Record the Average Speed value recorded from the exercise watch (if available)
        $avg_speed_code = "A";

        # "B" If the user click on the ‘Complete’ button to log the workout, use equation (Please see
        # Average Speed Calculation Guide).
        if ($avg_speed == 0) {
            $avg_speed = $this->calculateAverageSpeedGuide($trainingLog['exercise'], $total_distance, $total_duration_minutes);
            $avg_speed_code = "B";
        }

        return [
            'avg_speed'        =>     round($avg_speed, 2),
            'avg_speed_unit'    =>   $this->avg_speed_unit,
            'avg_speed_code'   =>     $avg_speed_code
        ];
    }

    public function calculateTotalKcalBurn($trainingLog, $power, $BMI,  $total_lvl,  $total_rpm, $total_duration_minutes)
    {
        # B case code here.
        $isWatt = $trainingLog['exercise'][0]['watt'];

        /** 1
         * Step 1 (Find VO2):
         */
        $VO2 = 0;

        $allAddedWatt = 0;
        if (isset($isWatt)) {
            /* 
            * If user is using Watt in the training log:
            * A) kgm/min = Watt x 6.118
            * B) VO2 for lower body = 1.8 x (kgm/min / body mass) + 7
            * C) VO2 for upper body = 3 x (kgm/min /body mass) + 3.5
            * D) Total VO2 = (B) + (C)
            */

            // A) kgm/min = Watt x 6.118
            /** add all watt from laps  */
            $allAddedWatt  = collect($trainingLog['exercise'])->sum('watt');
            $kgm_min = $allAddedWatt *  6.118;

            // B) VO2 for lower body = 1.8 x (kgm/min / body mass) + 7
            $vo2_lower_body = 1.8 * ($kgm_min / $BMI) + 7;

            // C) VO2 for upper body = 3 x (kgm/min /body mass) + 3.5
            $vo2_upper_body = 3 * ($kgm_min / $BMI) + 3.5;

            // D) Total VO2 = (B) + (C)
            $VO2 = $vo2_lower_body + $vo2_upper_body;
        } else {
            /**
             * If user is using RPM in the training log:
             * A) kgm/min = Lvl ** x 3 x rpm
             * B) VO2 for lower body = 1.8 x (kgm/min / body mass) + 7
             * C) VO2 for upper body = 3 x (kgm/min /body mass) + 3.5
             * D) Total VO2 = (B) + (C)
             *If user set ‘0’ for Lvl in the training log, use ‘1’ in the equation by default.
             */

            // A) kgm/min = Lvl ** x 3 x rpm
            $total_lvl_new = $total_lvl == 0 ? 1 : $total_lvl;
            $kgm_min = $total_lvl_new * 3 * $total_rpm;

            // B) VO2 for lower body = 1.8 x (kgm/min / body mass) + 7
            $vo2_lower_body = 1.8 * ($kgm_min / $BMI) + 7;

            // C) VO2 for upper body = 3 x (kgm/min /body mass) + 3.5
            $vo2_upper_body = 3 * ($kgm_min / $BMI) + 3.5;

            // D) Total VO2 = (B) + (C)
            $VO2 = $vo2_lower_body + $vo2_upper_body;
        }

        /**
         * Step 2 (Find MET value): 
         * MET = VO2/ 3.5 
         */
        $VO2 = round($VO2, 2);
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
            'total_watt' => $allAddedWatt,
            "total_kcal"    =>  round($total_kcal, 2)
        ];
    }
}
