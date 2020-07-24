<?php

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use App\Http\Controllers\Controller;
use App\Supports\SummaryCalculationTrait;
use Illuminate\Support\Arr;

class CycleCalculationsController extends Controller
{
    use SummaryCalculationTrait;

    protected $total_distance_unit;
    protected $avg_speed_unit;
    protected $average_power_unit;
    protected $total_relative_power_unit;
    protected $elevation_gain_unit;
    protected $gradient_unit;

    public function __construct()
    {
        $this->total_distance_unit = "km";
        $this->avg_speed_unit = "km/hr";
        $this->average_power_unit = "W";
        $this->gradient_unit = "%";
        $this->elevation_gain_unit = "m";
        $this->total_relative_power_unit = "W/kg";
    }

    /**
     * generateCalculation => CYCLE ( IN | OUT )
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

        # Lvl  
        $calculateLVL = $this->calculateLVL($trainingLog);
        $response = array_merge($response, $calculateLVL);

        # RPM  
        $calculateRPM = $this->calculateRPM($trainingLog);
        // dd('check rpm', $calculateRPM);
        $response = array_merge($response, $calculateRPM);

        # START Total Duration 
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

        # Start Average RPM
        $calculateAverageRPM = $this->calculateAverageRPM(
            $trainingLog['exercise'],
            $activityCode,
            $response['avg_speed']
        );
        $response = array_merge($response, $calculateAverageRPM);
        # End Average RPM

        # Start Elevation Gain
        $calculateElevationGain = $this->calculateElevationGain(
            $trainingLog['exercise'],
            $activityCode
            // $response['total_distance'],
            // $response['total_duration_minutes']
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

        # Start BMI
        $response['BMI'] = $BMI =
            round(
                ((
                    ($trainingLog['user_detail']['weight'] ?? 0)
                    / ($trainingLog['user_detail']['height'] ?? 0)
                    / ($trainingLog['user_detail']['height'] ?? 0))
                    * 10000),
                2
            );
        # End BMI

        # Start Average Power (unit in W)
        $calculatePower = $this->calculatePower(
            $trainingLog,
            $activityCode,
            $response['gradient'],
            $response['avg_speed'],
            $BMI,
            $response['average_rpm']
        );
        $response = array_merge($response, $calculatePower);
        # End Average Power (unit in W)

        # Relative Power (unit in W/kg)
        $calculateRelativePower = $this->calculateRelativePower(
            $response['average_power'],
            $trainingLog['user_detail']['weight']
        );
        $response = array_merge($response, $calculateRelativePower);

        # Total kcal Burnt 
        $calculateTotalKcalBurn = $this->calculateTotalKcalBurn(
            $trainingLog,
            $response['average_power'],
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
     * calculateTotalDistance => Calculate Total Duration condition wise
     *
     * @param  mixed $trainingLog
     * @param  mixed $activityCode
     * @param  mixed $isDuration
     * @return void
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
                $total_distance = Arr::first($exerciseTotalDistanceArray);
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
        // dd('sum of all distance ', $total_distance, $trainingLog['exercise']);
        // "Total Duration" End Calculate  -------------------------------------------
        return [
            'total_distance' => round($total_distance, 2),
            'total_distance_unit' =>  $this->total_distance_unit,
            'total_distance_code' => $total_distance_code
        ];
    }
    
    /**
     * calculateLVL
     *
     * @param  mixed $trainingLog
     * @return void
     */
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

    /**
     * calculateRPM
     *
     * @param  mixed $trainingLog
     * @return void
     */
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
    
    /**
     * calculateDuration
     *
     * @param  mixed $trainingLog
     * @param  mixed $isDuration
     * @return void
     */
    public function calculateDuration($trainingLog, $isDuration)
    {
        // dd('duration', $trainingLog);
        $totalDurationMinute = 0;

        # A) Use phone tracker (when user starts the workout log to when the workout log ends). 
        $start_time = collect($trainingLog['exercise'])->where('start_time', '<>', null)->pluck('start_time')->first();
        $end_time = collect($trainingLog['exercise'])->where('end_time', '<>', null)->pluck('end_time')->first();

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
            // $totalDurationMinute = $this->addAllDurationTimeFromExercise($trainingLog['exercise']); // OLD
            $totalDurationMinute = $this->addAllDurationAndRestTimeFromExercise($trainingLog['exercise']); // NEW
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
            'total_duration' => $this->convertDurationMinutesToTimeFormat($totalDurationMinute),
            'total_duration_code' => $totalDurationMinuteCode,
        ];
    }
    
    /**
     * calculatePower
     *
     * @param  mixed $trainingLog
     * @param  mixed $activityCode
     * @param  mixed $gradient
     * @param  mixed $avg_speed
     * @param  mixed $BMI
     * @param  mixed $RPM
     * @return void
     */
    public function calculatePower($trainingLog, $activityCode, $gradient, $avg_speed, $BMI, $RPM)
    {
        $average_power = 0;
        $isWatt = $trainingLog['exercise'][0]['watt'];

        $total_weight = 20; // FIXME solve this ->  // get from setting // currently static use

        /** user weight */
        $userWeight = $trainingLog['user_detail']['weight'];
        $isUserGenderMale = $trainingLog['user_detail']['gender'] == GENDER_MALE ?? false;
        $user_date_of_birth = $trainingLog['user_detail']['date_of_birth'];

        # A) Record the Average Power value recorded from the power meter (if available). // FIXME - Remain

        if ($average_power == 0 && $activityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            # B) For Outdoor use, find Average Power with equation (doesn’t matter if user uses ‘Watt’ or
            # ‘RPM’ parameter in the training log):

            // FIXME - Pending code
            # Step 1) Power 1 (Rolling resistance) = 9.81 x COS(ATAN(Gradient/100)) x Total weight x 0.004
            $power_1 = 9.81 * COS(
                ATAN(
                    ($gradient == 0 ? 1 : $gradient) / 100
                )
            ) * $total_weight * 0.004;
            # Step 2) Power 2 (Drag) = 0.5 x 1.2 x ((average speed in km/hr x 5)/18)2 x 0.9 x 0.423
            $power_2 = 0.5 * 1.2 * (((($avg_speed * 5) / 18) * ((($avg_speed * 5) / 18)))) * 0.9 * 0.423;
            # Step 3) Power 3 (Gravity) = Total weight x 9.81 x SIN(ATAN(Gradient/100)
            $power_3 = $total_weight * 9.81 * SIN(ATAN($gradient / 100));
            # Step 4) Total average power = Power 1 + Power 2 + Power 3
            $total_average_power = $power_1 + $power_2 + $power_3;
            # Step 5) Convert unit to Average Watt = Total power x ((average speed in km/hr x 5)/18)
            $average_watt = $total_average_power * (($avg_speed * 5) / 18);
            # Step 6) Average Watt with power loss factor = ((1-(2/100))-1 x Step 5
            // $average_power = $averageWattWithPowerLossFactor = ((1 - (2 / 100)) ^ -1) * $average_watt;
            # ((1 - (2 / 100)) ^ -1) = 1.0204
            $average_power = $averageWattWithPowerLossFactor = (1.0204) * $average_watt;
            // dd('asd check', $average_power,  $average_watt);
            $average_power_code = "B";
        }

        # C) For Indoor use OR if the user click on the ‘Complete’ button to log the workout, use
        # equation (if user use ‘Watt’ in the training log):
        if ($average_power == 0 && isset($isWatt)) {
            # Step 1) Add all the Watt values in the training log to get ‘total watt’.
            $total_watt = collect($trainingLog['exercise'])->sum('watt');
            # Step 2) Average Power = Step 1/ Total number of laps in the training log
            $average_power = $total_watt / count($trainingLog['exercise']);
            $average_power_code = 'C';
        }

        # D) For Indoor use OR if the user click on the ‘Complete’ button to log the workout, use
        # equation (if user use ‘RPM’ in the training log):
        if ($isWatt == null && $activityCode == TRAINING_ACTIVITY_CODE_CYCLE_INDOOR) {
            # Step 1) Determine PA score
            $PA_score = 0;
            $Resting_HR = 0;
            # Step 2) MET = (Gender * 2.77)-(Age * 0.1)-(BMI * 0.17)-(Resting HR * 0.03)+(PA score * 1.00)+18.07
            $genderValue = $isUserGenderMale == true ? 1 : 0;

            $age = $this->getAgeFromDateOfBirth($user_date_of_birth);
            // dd('check BMI ', $BMI, $trainingLog);
            $MET = ($genderValue * 2.77) - ($age * 0.1) - ($BMI * 0.17) - ($Resting_HR * 0.03) + ($PA_score * 1.00) + 18.07;
            # Step 3) VO2max = Step 2 * 3.5
            $VO2max = $MET * 3.5;
            # Step 4) %HRmax = (Average HR / HRMax) * 100
            $Average_HR = $this->calculateAverageHRFromTrainingGoal($trainingLog['training_intensity'], $age);

            $HRMaxPer = ($Average_HR / $this->getHRMaxByAge($age)) * 100;
            // dd('asd', $Average_HR, $HRMaxPer,  $trainingLog);
            # Step 5) %VO2max = (Step 4 – 37.182) / 0.6463
            $VO2maxPer = ($HRMaxPer - 37.182) / 0.6463;
            # Step 6) VO2 = Step 3 * (Step 5 / 100)
            $VO2 = $VO2max * ($VO2maxPer / 100);
            # Step 7) Average Power = (Step 6 – 3.5 – 0.0000076 * RPM3 ) / (10.8 * User weight in kg)
            $average_power = ($VO2 - 3.5 - 0.0000076 * (pow($RPM, 3))) / (10.8 * $userWeight);

            $average_power_code = 'D';
        }
        // dd('asd', $average_power, $average_power_code);

        return [
            'average_power' => round($average_power, 2),
            'average_power_unit' => $this->average_power_unit,
            'average_power_code' => $average_power_code
        ];
    }
    
    /**
     * calculateRelativePower
     *
     * @param  mixed $average_power
     * @param  mixed $userBodyWeight
     * @return void
     */
    public function calculateRelativePower($average_power, $userBodyWeight)
    {
        $total_relative_power = 0;

        # A) Record the relative power Watt value recorded from the power meter.    
        // TODO Remain from Device Side
        $total_relative_power_code = "A";

        # B) Use this equation to find Relative Power: 
        // Relative Power = Power / Body weight (in kg)
        // dd('asd', $average_power,  $userBodyWeight);
        $total_relative_power = $average_power / ($userBodyWeight ?? 0);
        $total_relative_power_code = "B";

        return [
            'total_relative_power' => round($total_relative_power, 2),
            'total_relative_power_unit' => $this->total_relative_power_unit,
            'total_relative_power_code' => $total_relative_power_code
        ];
    }
    
    /**
     * calculateAverageSpeed
     *
     * @param  mixed $exercises
     * @param  mixed $activityCode
     * @param  mixed $total_distance
     * @param  mixed $total_duration_minutes
     * @return void
     */
    public function calculateAverageSpeed($exercises, $activityCode, $total_distance, $total_duration_minutes)
    {
        $avg_speed = 0;

        $trainingActivityCode = $activityCode;

        # B) Record the Average Speed value recorded from the power meter (if available). 
        $avg_speed_code = "B";

        # C) Record the Average Speed value recorded from the exercise watch (if available) 
        $avg_speed_code = "C";

        # D) Use equation (Please see Average Speed Calculation Guide). 
        $avg_speed_code = "D";
        $avg_speed = $this->calculateAverageSpeedGuide($exercises, $total_distance, $total_duration_minutes);

        # A) Use phone GPS/tracker and apply this equation:
        // Average Speed = Total Distance (in km) / Total Duration (convert to minutes) (only for Outdoor) 
        if ($trainingActivityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            $avg_speed = $total_distance / $total_duration_minutes;
            // dd('check in or out',  $avg_speed, $total_distance, $total_duration_minutes);
            $avg_speed_code = "A";
        }
        // dd('check in or out', $trainingActivityCode,         $total_distance, $total_duration_minutes);

        return [
            'avg_speed' => round($avg_speed, 2),
            'avg_speed_unit' => $this->avg_speed_unit,
            'avg_speed_code' => $avg_speed_code
        ];
    }

    public function calculateTotalKcalBurn($trainingLog, $power, $BMI,  $total_lvl,  $total_rpm, $total_duration_minutes)
    {
        $total_kcal = 0;

        # A) Record the Total kcal value recorded from the power meter (if available). // NEW
        // $total_kcal_code = "A";

        if ($total_kcal == 0) {
            # B) Record the Total kcal value recorded from the exercise watch (if available). // NEW
            // $total_kcal_code = "B";

        }
        if ($total_kcal == 0) {
            # C) Find Total kcal with equation: // NEW
            # Total kcal = Average Watt  Total Duration in hr x 3.6 // NEW
            $AverageWatt = 0;
            $total_kcal = $AverageWatt / round(($total_duration_minutes / 60), 4) * 3.6; // NEW
            $total_kcal_code = "C";
        }
        $isWatt = $trainingLog['exercise'][0]['watt'];

        // if (isset($isWatt)) { // OLD
        /**
         * Step 1 (Find VO2): 
         * If user is using Watt in the training log: 
         * VO2 = 1.8 x ((power x 6) / body mass) + 7
         */
        // $VO2 = 1.8 * (($power * 6) / $BMI) + 7; // OLD
        // } else {  // OLD
        /**
         * If user is using RPM in the training log: 
         * VO2 = 1.8 x ((*Lvl x 6.12 x RPM)/ body mass) + 7 
         */
        // $tot_lvl = $total_lvl == 0 ? 1 : $total_lvl; // OLD
        // $VO2 = 1.8 * ((($tot_lvl) * 6.12 * $total_rpm) / $BMI) + 7; // OLD
        // } // OLD
        // $VO2 = round($VO2, 2); // OLD
        /**
         * Step 2 (Find MET value): 
         * MET = VO2/ 3.5 
         */
        // $MET = round(($VO2 / 3.5), 2); // OLD

        /**
         * Step 3 (Find kcal/min): 
         * kcal/min = (MET x 3.5 x body mass in kg) / 200 
         */
        // $kcal_min = round((($MET * 3.5 * $BMI) / 200), 2); // OLD

        /**
         * Step 4 (Final total kcal): 
         * Total kcal = (kcal/min) x total duration in mins
         */
        // $total_kcal = round((($kcal_min) * $total_duration_minutes), 2); // OLD
        return [
            "total_kcal"    =>  round($total_kcal, 2),
            "total_kcal_code"    =>  round($total_kcal_code, 2)
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

    public function calculateElevationGain($exercises, $activityCode)
    {
        $elevation_gain = 0;
        $elevation_gain_code = null;

        if ($elevation_gain == 0 && $activityCode == TRAINING_ACTIVITY_CODE_RUN_OUTDOOR) {
            # A) Use phone location sensor (Barometer) (only for Outdoor)

            # B) Record the Elevation Gain value recorded from the power meter, exercise watch or
            // phone with barometric altimeter (if available).
            if ($elevation_gain == 0) {
                $elevation_gain_code = "B";
            }

            if ($elevation_gain == 0) {
                $elevation_gain_code = "C";
            }
            # C) If the user click on the ‘Complete’ button to log the workout, Elevation Gain will show ‘-‘
        }

        // dd('as', $this->elevation_gain_unit);
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
            $gradient = ($elevation_gain / $total_distance_in_meters) * 100;
            $gradient_code = "A";

            # B) If Elevation Gain value is not available, Gradient will show '-'
            if ($gradient == 0 || in_array($elevation_gain, [0, 0.0, null, ''])) {
                $gradient = 0;
                $gradient_code = "B";
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
        }

        return [
            'gradient' => $gradient ?? null,
            'gradient_unit' =>  $this->gradient_unit,
            'gradient_code' => $gradient_code
        ];
    }

    public function calculateAverageHRFromTrainingGoal($training_intensity, $age)
    {
        $target_hrArray = explode('-', $training_intensity['target_hr']);

        // 206.9 - (0.67 * $age) 
        $HRMax = $this->getHRMaxByAge($age);
        // $HR_Cycling = $HRMax * (5 / 100); // OLD 
        $HR_Cycling = $HRMax * ($HRMax / 100); // NEW

        $Lower_Limit = $HR_Cycling * ($target_hrArray[0] / 100);
        $Upper_Limit = $HR_Cycling * ($target_hrArray[1] / 100);

        $Average_HR = ($Lower_Limit  + $Upper_Limit) / 2;
        return $Average_HR;
    }

    /**
     * getHRMaxByAge
     *
     * @param  mixed $age
     * @return void
     */
    public function getHRMaxByAge($age)
    {
        return round(206.9 - (0.67 * $age), 2);
    }

    public function calculateAverageRPM($exercises, $activityCode, $avg_speed)
    {
        $averageRPM = 0;
        $isWatt = $exercises[0]['watt'];
        // dd('got code', $activityCode, $isWatt, $exercises);

        $front_chain_wheel = 0;
        $rear_free_wheel = 0;
        $wheel_diameter = 0;

        # $averageRPM = # FIXME - get from power watch
        // $average_rpm_code = 'A'; 

        if ($isWatt !== null) {
            # Combined C and D with same condition
            if ($averageRPM == 0 and $activityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
                # B) For Outdoor use, find Average RPM with equation (if user use ‘Watt’ in the training log):
                # Step 1) Distance travelled with 1 rev (m or yards) = ((Front chain wheel/ Rear free wheel) * (Wheel diameter x 3.14159)) / 1000
                $distance_travelled_with_1_rev = (($front_chain_wheel / $rear_free_wheel) * ($wheel_diameter * 3.14159)) / 1000;
                # Step 2) Convert Step 1 unit (km or miles) = (Step 1 / 1000) x 60
                $convert_step_1_unit = ($distance_travelled_with_1_rev / 1000) * 60;
                # Step 3) Average RPM = Average speed / Step 2
                $averageRPM = $avg_speed / $convert_step_1_unit;
                $average_rpm_code = 'B';
            }

            if ($averageRPM == 0 and $activityCode == TRAINING_ACTIVITY_CODE_CYCLE_INDOOR) {
                # C) For Indoor use, find Average RPM with equation (if user use ‘Watt’ in the training log):
                # Step 1) Distance travelled with 1 rev (km or miles) = (6  1000) x 60
                $distance_travelled_with_1_rev = (6 / 1000) * 60;
                # Step 2) Average RPM = Average speed  Step 1
                $averageRPM  = $avg_speed / $distance_travelled_with_1_rev;
                $average_rpm_code = 'C';
            }
        }
        if ($averageRPM == 0 and $isWatt == null) {
            # D) For Indoor and Outdoor use OR if the user click on the ‘Complete’ button to log the workout, use equation (if user use ‘RPM’ in the training log):
            # Step 1) Total RPM = Add all the RPM value(s) in the training log.
            $TotalRPM = collect($exercises)->sum();
            # Step 2) Average RPM = Total RPM  Total lap(s)
            $averageRPM  = $TotalRPM / count($exercises);
            $average_rpm_code = 'D';
        }

        return [
            'average_rpm' => round($averageRPM, 2) ?? null,
            // 'gradient_unit' =>  $this->gradient_unit,
            'average_rpm_code' => $average_rpm_code
        ];
    }
}
