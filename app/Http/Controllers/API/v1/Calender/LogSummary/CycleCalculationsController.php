<?php

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use App\Http\Controllers\Controller;
use App\Libraries\Repositories\SettingTrainingRepositoryEloquent;
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

        # Calculate Active Duration and Minutes
        if ($activityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            $deActiveDuration = $trainingLog['exercise'][0]['deactive_duration']  ?? 0;
            $calculateActiveDuration = $this->calculateActiveDuration($response['total_duration_minutes'], $deActiveDuration);
            $response = array_merge($response, $calculateActiveDuration);
        }

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
        $calculateLVL = $this->calculateLVL($trainingLog, $activityCode);
        $response = array_merge($response, $calculateLVL);

        # RPM  
        $calculateRPM = $this->calculateRPM($trainingLog);
        $response = array_merge($response, $calculateRPM);

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
                1
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

        # Total kcal 
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
     * calculateDuration
     *
     * @param  mixed $trainingLog
     * @param  mixed $isDuration
     * @return void
     */
    public function calculateDuration($trainingLog, $isDuration)
    {
        # Outdoor and Indoor: A, B, C → D OR E
        $totalDurationMinute = 0;

        # A) If the user click on the ‘Start’ button, use phone tracker 
        # (when user starts the workout log to when the workout log ends).
        $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);
        $totalDurationMinuteCode = "A";

        # B) Record the Total Duration value recorded from the power meter (if available).
        # NOTE Remaining from Device side
        // $totalDurationMinuteCode = "B";

        # C) Record the Total Duration recorded from the exercise watch (if available).
        # NOTE Remaining from Device side
        // $totalDurationMinuteCode = "C";

        // (If user use ‘Duration’ in the log). 
        if (
            $isCompleteButton &&
            /** if user click on Complete button */
            isset($isDuration)
            /* && in_array(($totalDurationMinute * 60), range(TRAINING_WORKOUT_STOP_IMMEDIATELY_MIN_SECOND, TRAINING_WORKOUT_STOP_IMMEDIATELY_MAX_SECOND) ) */
        ) {
            # E) If the user click on the ‘Complete’ button to log the workout, use equation 
            # (If user use ‘Duration’ in the log):
            # Add all the Duration (including Rest) data keyed in the log.
            $totalDurationMinute = $this->addAllDurationAndRestTimeFromExercise($trainingLog['exercise']); // NEW
            $totalDurationMinuteCode = "E";
        } else if (
            $isCompleteButton &&
            /** if user click on Complete button */
            !isset($isDuration)
            /* && in_array(($totalDurationMinute * 60), range(TRAINING_WORKOUT_STOP_IMMEDIATELY_MIN_SECOND, TRAINING_WORKOUT_STOP_IMMEDIATELY_MAX_SECOND)) */
        ) {
            # D) If the user click on the 'Complete' button to log the workout, use equation 
            # (If user use ‘Distance’ in the log. Please see Duration Calculation Guide).
            $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
            $totalDurationMinuteCode = "D";
        }

        return [
            'total_duration_minutes' => round($totalDurationMinute, 1),
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
        $deActiveDuration = ($deActiveDuration ?? 0) / 60; // to convert into minute
        $totalDurationMinute = round($totalDurationMinute, 2);
        $deActiveDuration = round($deActiveDuration, 2);
        $activeDurationMinute = round(($totalDurationMinute - $deActiveDuration), 2);

        return [
            'active_duration_minutes' => $activeDurationMinute,
            'active_duration' => $this->convertDurationMinutesToTimeFormat($activeDurationMinute)
        ];
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
        // Outdoor: A, B, C → D OR E
        // Indoor: B, C → D OR E

        $total_distance = 0;

        $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        # A) If the user click on the ‘Start’ button, use phone location and motion sensors 
        # (GPS + Accelerometer) (only for Outdoor)
        if ($activityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            // NOTE - Remain To Test
            $total_distance = $this->getTotalDistanceFromStartEndLatitudeLongitude($trainingLog);
            $total_distance_code = "A";
        }

        if (!$isCompleteButton && $total_distance == 0) {
            #  B) If the user click on the ‘Start’ button, 
            # use the value recorded from the power meter (if available)
            // $total_distance_code = "B";
        }

        $lastExerciseTotalDistance = collect($trainingLog['exercise'])->whereNotIn('total_distance', ['0', 0, null])->pluck('total_distance')->first();
        # C) If the user click on the ‘Start’ button, 
        # use the value recorded from the exercise watch (if available)
        if (!$isCompleteButton && $total_distance == 0) {
            $total_distance = $this->getDistanceFromExerciseWatch($lastExerciseTotalDistance); // function in summary controller
            $total_distance_code = "C";
        }

        # D) If the user click on the ‘Complete’ button to log the workout, use equation 
        # (If user use ‘Duration’ in the log. Please see Distance Calculation Guide).
        if ($isCompleteButton && $total_distance == 0 && isset($isDuration)) {
            $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
            $total_distance_code = "D";
        }
        if ($isCompleteButton && !isset($isDuration)) {
            # E) If the user click on the ‘Complete’ button to log the workout, use equation 
            # (If user use ‘Distance’ in the log):
            # Add all the Distance data keyed in the log.
            $total_distance = collect($trainingLog['exercise'])->sum('distance');
            $total_distance_code = "E";
        }

        return [
            'total_distance' => round($total_distance, 1),
            'total_distance_unit' =>  $this->total_distance_unit,
            'total_distance_code' => $total_distance_code
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
        # Outdoor: A, B, C → D  
        # Indoor: B, C → D

        $avg_speed = 0;

        $trainingActivityCode = $activityCode;

        /** if $totalDurationMinute  is 0 Means *COMPLETE* button clicked */
        $totalDurationMinute = $this->totalDurationMinute(['exercise' => $exercises]);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        # A) If the user click on the ‘Start’ button, 
        # use phone location and motion sensors (GPS + Accelerometer) (only for Outdoor)
        if (!$isCompleteButton && $trainingActivityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            $avg_speed = $total_distance / ($total_duration_minutes / 60); // minute to hr
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            // if (isset($avg_pace)) {
            //     # convert pace to speed
            //     $avg_speed = (60 / $avg_pace);
            // }
            $avg_speed_code = "A";
        }

        if (!$isCompleteButton && $avg_speed == 0) {
            # B) If the user click on the ‘Start’ button, 
            # record the Average Speed value recorded from the power meter (if available).
            $avg_speed = $total_distance / ($total_duration_minutes / 60); // minute to hr
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            // if (isset($avg_pace)) {
            //     # convert pace to speed
            //     $avg_speed = (60 / $avg_pace);
            // }
            $avg_speed_code = "B";
        }
        $avg_speed = $avg_speed ?? 0; // convert null to 0 if null found

        if (!$isCompleteButton && $avg_speed == 0) {
            # C) If the user click on the ‘Start’ button, 
            # record the Average Speed value recorded from the
            # exercise watch (if available)
            // $avg_speed_code = "C";
        }

        if ($isCompleteButton && $avg_speed == 0) {
            # D) If the user click on the ‘Complete’ button to log the workout, use equation 
            # (Please see Average Speed Calculation Guide).
            $avg_speed = $this->calculateAverageSpeedGuide(
                $exercises,
                $total_distance,
                $total_duration_minutes
            );
            $avg_speed_code = "D";
        }

        return [
            'avg_speed' => round($avg_speed, 1),
            'avg_speed_unit' => $this->avg_speed_unit,
            'avg_speed_code' => $avg_speed_code
        ];
    }

    /**
     * calculateAvgPace
     * use in TrainingLog.php
     * @param  mixed $exercises
     * @param  mixed $totalDistance
     * @param  mixed $totalDurationMinute
     * @param  mixed $activityCode
     * @return void
     */
    public function calculateAvgPace($exercises, $totalDistance, $totalDurationMinute, $activityCode)
    {
        $avg_pace = 0;

        $avg_pace = $this->convertPaceNumberTo_M_S_format($avg_pace);
        // "avg pace" End Calculate  -------------------------------------------
        return [
            'avg_pace' => $avg_pace ?? null,
            'avg_pace_unit' =>  $this->avg_pace_unit ?? null,
            'avg_pace_code' => $avg_pace_code ?? null
        ];
    }

    /**
     * calculateLVL
     *
     * @param  mixed $trainingLog
     * @return void
     */
    public function calculateLVL($trainingLog, $activityCode)
    {

        // Outdoor: (Not applicable)
        // Indoor: A

        $total_lvl = 0;
        # A) For Indoor use OR if the user click on the ‘Complete’ button to log the workout, use equation:
        if ($activityCode == TRAINING_ACTIVITY_CODE_CYCLE_INDOOR) {
            # Step 1) Add all the Lvl values in the training log to get ‘total Lvl’.
            # Step 2) Lvl = Step 1/ Total number of laps in the training log
            $allLVL = collect($trainingLog['exercise'])->sum('lvl');
            $total_lvl = $allLVL / count($trainingLog['exercise']);
            $total_lvl_code = 'A';
        }

        return [
            'total_lvl' => round($total_lvl, 1),
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
        return [
            'total_rpm' => round($total_rpm, 2),
            'total_rpm_code' => $total_rpm_code,
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

        // Outdoor: A → B → C OR D
        // Indoor: A → C OR D

        $average_power = 0;
        $isWatt = $trainingLog['exercise'][0]['watt'];

        $settingTrainingRepository = app(SettingTrainingRepositoryEloquent::class);
        $userSettingTraining = $settingTrainingRepository->getDetailsByInput([
            'user_id' => \Auth::id(),
            'first' => true,
            'list' => ['id', 'user_id', 'bike_weight'],
        ]);
        $total_weight = (float) ($userSettingTraining->bike_weight * 1);  # from Settings → Training → Cardio (Bike Settings)

        /** user weight */
        $userWeight = $trainingLog['user_detail']['weight'];
        $isUserGenderMale = $trainingLog['user_detail']['gender'] == GENDER_MALE ?? false;
        $user_date_of_birth = $trainingLog['user_detail']['date_of_birth'];

        # A) If the user click on the ‘Start’ button, record the Average Power value recorded from the
        # power meter (if available).
        // FIXME - Pending for power meter
        // $average_power_code = "A";

        if ($average_power == 0 && $activityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            # B) For Outdoor use, find Average Power with equation (doesn’t matter if user uses ‘Watt’ or
            # ‘RPM’ parameter in the training log):
            // FIXME - Pending code

            # Step 1) Power 1 (Rolling resistance) = 9.81 x COS(ATAN(Gradient/100)) x Total weight x 0.004
            $gradient == 0 ? 1 : $gradient;
            $power_1 = 9.81 * COS(ATAN($gradient / 100)) * $total_weight * 0.004;

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
        // && $activityCode == TRAINING_ACTIVITY_CODE_CYCLE_INDOOR
        if ($average_power == 0 && $isWatt == null) {
            # Step 1) Determine PA score
            $PA_score = 0;
            $Resting_HR = 0;

            # Step 2) MET = (Gender * 2.77)-(Age * 0.1)-(BMI * 0.17)-(Resting HR * 0.03)+(PA score * 1.00)+18.07
            $genderValue = $isUserGenderMale == true ? 1 : 0;

            /** get user age from their Date of Birth. */
            $age = $this->getAgeFromDateOfBirth($user_date_of_birth);
            $MET = ($genderValue * 2.77) - ($age * 0.1) - ($BMI * 0.17) - ($Resting_HR * 0.03) + ($PA_score * 1.00) + 18.07;

            # Step 3) VO2max = Step 2 * 3.5
            $VO2max = $MET * 3.5;

            # Step 4) %HRmax = (Average HR / HRMax) * 100
            $Average_HR = $this->calculateAverageHRFromTrainingGoal($trainingLog['training_intensity'], $age);

            $HRMaxPer = ($Average_HR / $this->getHRMaxByAge($age)) * 100;

            # Step 5) %VO2max = (Step 4 – 37.182) / 0.6463
            $VO2maxPer = ($HRMaxPer - 37.182) / 0.6463;

            # Step 6) VO2 = Step 3 * (Step 5 / 100)
            $VO2 = $VO2max * ($VO2maxPer / 100);

            # Step 7) Average Power = (Step 6 – 3.5 – 0.0000076 * RPM3 ) / (10.8 * User weight in kg)
            $average_power = ($VO2 - 3.5 - 0.0000076 * (pow($RPM, 3))) / (10.8 * $userWeight);
            $average_power_code = 'D';
        }

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
        # Relative Power = Power / Body weight (in kg)
        $total_relative_power = $average_power / ($userBodyWeight ?? 0);
        $total_relative_power_code = "B";

        return [
            'total_relative_power' => round($total_relative_power, 2),
            'total_relative_power_unit' => $this->total_relative_power_unit,
            'total_relative_power_code' => $total_relative_power_code
        ];
    }

    /**
     * calculateTotalKcalBurn
     *
     * @param  mixed $trainingLog
     * @param  mixed $power
     * @param  mixed $BMI
     * @param  mixed $total_lvl
     * @param  mixed $total_rpm
     * @param  mixed $total_duration_minutes
     * @return void
     */
    public function calculateTotalKcalBurn($trainingLog, $power, $BMI,  $total_lvl,  $total_rpm, $total_duration_minutes)
    {
        // Outdoor and Indoor: A, B → C
        $total_kcal = 0;

        # A) Record the Total kcal value recorded from the power meter (if available).
        // $total_kcal_code = "A";

        if ($total_kcal == 0) {
            # B) Record the Total kcal value recorded from the exercise watch (if available).
            // $total_kcal_code = "B";
        }

        $isWatt = $trainingLog['exercise'][0]['watt'];

        if ($total_kcal == 0 && isset($isWatt)) {
            # C) Find Total kcal with equation:
            # Total kcal = Average Watt / Total Duration in hr x 3.6
            $AverageWatt = collect($trainingLog['exercise'])->avg('watt') ?? 0;
            $total_kcal = $AverageWatt / round(($total_duration_minutes / 60), 4) * 3.6;
            $total_kcal_code = "C";
        }

        return [
            "total_kcal" => round($total_kcal, 2),
            "total_kcal_code" => $total_kcal_code ?? null
        ];
    }

    /**
     * calculateElevationGain
     *
     * @param  mixed $exercises
     * @param  mixed $activityCode
     * @return void
     */
    public function calculateElevationGain($exercises, $activityCode)
    {

        // Outdoor: A, B → C
        // Indoor: (Not applicable)

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
     * define sample data
     * @return array
     */
    public function calculateGradient($exercises, $activityCode, $elevation_gain, $total_distance)
    {
        // Outdoor: A → B → C
        // Indoor: (Not applicable)

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

    /**
     * calculateAverageHRFromTrainingGoal
     *
     * @param  mixed $training_intensity
     * @param  mixed $age
     * @return void
     */
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

    /**
     * calculateAverageRPM
     *
     * @param  mixed $exercises
     * @param  mixed $activityCode
     * @param  mixed $avg_speed
     * @return void
     */
    public function calculateAverageRPM($exercises, $activityCode, $avg_speed)
    {
        // Outdoor: A → B OR D
        // Indoor: A → C OR D

        $averageRPM = 0;
        $isWatt = $exercises[0]['watt'];

        # NOTE: The value for ‘Front chainwheel’, ‘Rear freewheel’ and ‘Wheel diameter’ needs to be
        # retrieved from Settings → Training → Cardio (Bike Settings)
        $settingTrainingRepository = app(SettingTrainingRepositoryEloquent::class);
        $userSettingTraining = $settingTrainingRepository->getDetailsByInput([
            'user_id' => \Auth::id(),
            'first' => true,
            'list' => ['id', 'user_id', 'bike_weight', 'bike_front_chainwheel', 'bike_rear_freewheel', 'bike_wheel_diameter'],
        ]);
        $front_chain_wheel = $userSettingTraining->bike_front_chainwheel ?? 0;
        $rear_free_wheel = $userSettingTraining->bike_rear_freewheel ?? 0;
        $wheel_diameter = $userSettingTraining->bike_wheel_diameter ?? 0;

        # $averageRPM = # FIXME - get from power watch
        // $average_rpm_code = 'A'; 

        if ($averageRPM == 0 and $activityCode == TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR) {
            # B) For Outdoor use, find Average RPM with equation (if user use ‘Watt’ in the training log):

            # Step 1) Distance travelled with 1 rev (m or yards) = ((Front chain wheel/ Rear free wheel) * (Wheel diameter x 3.14159)) / 1000
            $front_by_rear =  $rear_free_wheel == 0 ? 0 : ($front_chain_wheel / $rear_free_wheel);
            $distance_travelled_with_1_rev = ($front_by_rear * ($wheel_diameter * 3.14159)) / 1000;

            # Step 2) Convert Step 1 unit (km or miles) = (Step 1 / 1000) x 60
            $convert_step_1_unit = ($distance_travelled_with_1_rev / 1000) * 60;

            # Step 3) Average RPM = Average speed / Step 2
            $averageRPM =  $convert_step_1_unit == 0 ? 0 : $avg_speed / $convert_step_1_unit; // error divided by Zero.
            $average_rpm_code = 'B';
        } elseif ($averageRPM == 0 and $activityCode == TRAINING_ACTIVITY_CODE_CYCLE_INDOOR) {

            # C) For Indoor use, find Average RPM with equation (if user use ‘Watt’ in the training log):
            # Step 1) Distance travelled with 1 rev (km or miles) = (6 / 1000) x 60
            $distance_travelled_with_1_rev = (6 / 1000) * 60;
            # Step 2) Average RPM = Average speed / Step 1
            $averageRPM  = $avg_speed / $distance_travelled_with_1_rev;
            $average_rpm_code = 'C';
        }

        if ($averageRPM == 0 && $isWatt == null) {
            # D) For Indoor and Outdoor use OR if the user click on the ‘Complete’ button to log the workout,
            # use equation (if user use ‘RPM’ in the training log):
            # Step 1) Total RPM = Add all the RPM value(s) in the training log.
            $TotalRPM = collect($exercises)->sum();
            # Step 2) Average RPM = Total RPM / Total lap(s)
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
