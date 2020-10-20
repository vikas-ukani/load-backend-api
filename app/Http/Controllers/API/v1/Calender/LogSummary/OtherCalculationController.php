<?php

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use App\Http\Controllers\Controller;
use App\Supports\SummaryCalculationTrait;
use Illuminate\Support\Arr;

class OtherCalculationController extends Controller
{
    use SummaryCalculationTrait;

    protected $total_distance_unit;
    protected $avg_speed_unit;
    protected $avg_pace_unit;
    protected $total_power_unit;
    protected $average_power_unit;
    protected $total_relative_power_unit;

    public function __construct()
    {
        $this->total_distance_unit = "km";
        $this->avg_speed_unit = "km/hr";
        $this->avg_pace_unit = "min/100m";
        $this->total_power_unit = "W";
        $this->average_power_unit = "W";
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
        // START MAIN
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
        # Total Duration END

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
        # END Total Distance 

        # Lvl  
        $calculateLVL = $this->calculateLVL($trainingLog);
        $response = array_merge($response, $calculateLVL);
        # 2. Lvl  END

        # 3. RPM  
        $calculateRPM = $this->calculateRPM($trainingLog);
        $response = array_merge($response, $calculateRPM);
        # 3. RPM  END

        # Start Average Power (unit in W)
        $calculateAveragePower = $this->calculateAveragePower(
            $trainingLog['exercise']
        );
        $response = array_merge($response, $calculateAveragePower);
        # End Average Power 

        # Relative Power (unit in W/kg)
        $calculateRelativePower = $this->calculateRelativePower(
            $response['average_power'],
            $trainingLog['user_detail']['weight']
        );
        $response = array_merge($response, $calculateRelativePower);
        # Relative Power (unit in W/kg) END

        # Average Speed
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
        # Average Speed END

        # start Average Pace
        /** get first from generated calculation */
        if (isset($trainingLog['generated_calculations'], $trainingLog['generated_calculations']['avg_pace'])) {
            $response = array_merge($response, [
                'avg_pace' => $trainingLog['generated_calculations']['avg_pace'],
                'avg_pace_unit' => $this->avg_pace_unit
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
        # end Average Pace

        # Start Average Heart Rate
        $averageHeartRate = $this->averageHeartRate($trainingLog['exercise']);
        $response = array_merge($response, $averageHeartRate);
        # End Average Heart Rate

        # Start Level
        $calculateLevel = $this->calculateLevel($trainingLog['exercise']);
        $response = array_merge($response, $calculateLevel);
        # End Level

        # Start Average RPM
        $calculateAverageRPM = $this->calculateAverageRPM($trainingLog['exercise']);
        $response = array_merge($response, $calculateAverageRPM);
        # End Average RPM

        # Total kcal 
        $response['BMI'] = $BMI = round(((($trainingLog['user_detail']['weight'] ?? 0) / ($trainingLog['user_detail']['height'] ?? 0)  / ($trainingLog['user_detail']['height'] ?? 0)) * 10000), 2);
        $usersWeight = $trainingLog['user_detail']['weight'] ?? 0;
        $calculateTotalKcalBurn = $this->calculateTotalKcalBurn(
            $trainingLog,
            $usersWeight,
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateTotalKcalBurn);
        # Total kcal Burnt END 

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
        # A, B → C OR D OR E
        $totalDurationMinute = 0;

        # A) If the user click on the ‘Start’ button, use phone tracker 
        # (when user starts the workout log to when the workout log ends).
        $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);
        $totalDurationMinuteCode = "A";

        if (!$isCompleteButton && $totalDurationMinute == 0) {
            # B) If the user click on the ‘Start’ button, 
            # record the Total Duration recorded from the exercise watch (if available).
            // $totalDurationMinuteCode = "B";
        }

        if ($isCompleteButton && $totalDurationMinute == 0) {
            # C) If the user click on the ‘Complete’ button to log the workout, use equation 
            # (If user use ‘Distance’ and ‘Speed/Pace’ in the log. Please see Duration Calculation Guide).
            $totalDurationMinute = $this->calculateDurationCalculationGuid($trainingLog['exercise']);
            $totalDurationMinuteCode = "C";
        }

        if ($isCompleteButton && $totalDurationMinute == 0) {
            # D) If the user click on the ‘Complete’ button to log the workout, use equation 
            # (If user use ‘Duration’ in the log): Add all the Duration (including Rest) data keyed in the log.
            $totalDurationMinute = $this->addAllDurationAndRestTimeFromExercise($trainingLog['exercise']);
            $totalDurationMinuteCode = "D";
        }

        if ($isCompleteButton && $totalDurationMinute == 0) {
            # E) If the user click on the ‘Complete’ button to log the workout but user did not key in
            # ‘Speed/ Pace’ parameter, Total Duration will show ‘-‘.
            $totalDurationMinute = 0;
            $totalDurationMinuteCode = "E";
        }

        return [
            'total_duration_minutes' => round($totalDurationMinute, 1),
            'total_duration' => $this->convertDurationMinutesToTimeFormat($totalDurationMinute),
            'total_duration_code' => $totalDurationMinuteCode
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
        # A, B, C → D OR E OR F
        $totalDurationMinute = $this->totalDurationMinute($trainingLog);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        $total_distance = 0;
        # A) If the user click on the ‘Start’ button,
        # use phone location and motion sensors  (GPS + Accelerometer) (if there is a change in position)
        if (!$isCompleteButton) {
            $total_distance = $this->getTotalDistanceFromStartEndLatitudeLongitude($trainingLog);
            $total_distance_code = "A";
        }

        if (!$isCompleteButton && $total_distance == 0) {
            # B) If the user click on the ‘Start’ button,
            # use phone motion sensor (Accelerometer) (if there is no change in position)
            $exerciseTotalDistanceArray = collect($trainingLog['exercise'])->whereNotIn('total_distance',  ['0', null, 0])->pluck('total_distance')->all();
            if (isset($exerciseTotalDistanceArray) && is_array($exerciseTotalDistanceArray) && count($exerciseTotalDistanceArray) > 0) {
                /** all meter data convert to KM */
                $total_distance = Arr::first($exerciseTotalDistanceArray); // get from first lap
                $total_distance = round(($total_distance * 0.001), 1);
                $total_distance_code = "B";
            }
        }

        $lastExerciseTotalDistance = collect($trainingLog['exercise'])->whereNotIn('total_distance', ['0', 0, null])->pluck('total_distance')->first();
        if (!$isCompleteButton && $total_distance == 0 && isset($lastExerciseTotalDistance)) {
            # C) If the user click on the ‘Start’ button, 
            # record the Total Distance value recorded from the exercise watch (if available).
            $total_distance = $this->getDistanceFromExerciseWatch($lastExerciseTotalDistance); // function in summary controller
            $total_distance_code = "C";
        }

        if ($isCompleteButton && $total_distance == 0) {
            if (isset($isDuration)) {
                # D) If the user click on the ‘Complete’ button to log the workout, use equation 
                # (If user use ‘Duration’ and ‘Speed/Pace’ in the log. Please see Distance Calculation Guide).
                $total_distance = $this->findTotalDistanceUsingDuration($trainingLog['exercise']);
                $total_distance_code = "D";
            } else {
                # E) If the user click on the ‘Complete’ button to log the workout, use equation 
                # (If user use ‘Distance’ in the log):
                # Add all the Distance data keyed in the log.
                $total_distance = collect($trainingLog['exercise'])->sum('distance');
                $total_distance_code = "E";
            }
        }

        if ($isCompleteButton && $total_distance == 0) {
            # F) If the user click on the ‘Complete’ button to log the workout but user did not key in
            # ‘Speed/ Pace’ parameter, Total Distance will show ‘-‘.
            $total_distance_code = "F";
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
     * @param  mixed $total_distance
     * @param  mixed $total_duration_minutes
     * @return array
     */
    public function calculateAverageSpeed($exercises, $activityCode,  $total_distance, $total_duration_minutes)
    {
        # A, B, C → D OR E
        $avg_speed = 0;

        /** if $totalDurationMinute  is 0 Means *COMPLETE* button clicked */
        $totalDurationMinute = $this->totalDurationMinute(['exercise' => $exercises]);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        $isPace = !isset($exercises[0]['is_speed']); // New key given by yash
        $this->avg_speed_unit = $isPace ?  /* PACE selected */ "m/min"  : /* SPEED selected  */ "km/hr";

        # A) If the user click on the ‘Start’ button, use phone location and motion sensors 
        # (GPS + Accelerometer) (if there is a change in position/ movement)
        /** use this told by yash */

        $avg_speed = $total_distance / $total_duration_minutes;
        // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
        // if (isset($avg_pace)) {
        //     # convert pace to speed
        //     $avg_speed = (60 / $avg_pace);
        // }
        $avg_speed_code = "A";


        if (!$isCompleteButton && $avg_speed == 0) {
            # B) If the user click on the ‘Start’ button, use phone motion sensor (Accelerometer) 
            # (if there is no change in position/ movement)
            $avg_speed = $total_distance / $total_duration_minutes;

            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            // if (isset($avg_pace)) {
            //     # convert pace to speed
            //     $avg_speed = (60 / $avg_pace);
            // }
            $avg_speed_code = "B";
        }

        if (!$isCompleteButton && $avg_speed == 0) {
            # C) If the user click on the ‘Start’ button, record the Average Speed value recorded from the exercise watch (if available)
            // $avg_speed_code = "C";
        }

        if ($isCompleteButton && $avg_speed == 0) {
            if (!$isPace) {
                # D) If the user click on the ‘Complete’ button to log the workout, use equation (If user uses
                # ‘Speed’ parameter, please see Average Speed Calculation Guide (NOTE: Stop at ‘Average Speed’ step).
                #  If user uses ‘Pace’ parameter, please see Average Pace Calculation Guide_Others to find Average Speed.
                $avg_speed = $this->calculateAverageSpeedGuide_OTHER($exercises);
            } else if ($isPace) {
                # If user uses ‘Pace’ parameter, please see Average Pace Calculation Guide_Others to find Average Speed.
                $avg_speed = $this->calculatePaceCalculationGuidForOTHER($exercises);
            }
            $avg_speed_code = "D";
        }

        if ($avg_speed == 0) {
            # E) If user did not key in ‘Speed/ Pace’ parameter, Average Speed will show ‘-’.
            $avg_speed_code = "E";
        }

        return [
            'avg_speed' => round($avg_speed, 1),
            'avg_speed_unit' => $this->avg_speed_unit,
            'avg_speed_code' => $avg_speed_code
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

        /** if $totalDurationMinute  is 0 Means *COMPLETE* button clicked */
        $totalDurationMinute = $this->totalDurationMinute(['exercise' => $exercises]);
        $isCompleteButton = (bool) ($totalDurationMinute == 0);

        $isPace = !isset($exercises[0]['is_speed']); // New key given by yash
        $this->avg_speed_unit = $isPace ?  /* PACE selected */ "min/500m"  : /* SPEED selected  */ "min/km";

        # A) If the user click on the ‘Start’ button, 
        # use phone location and motion sensors (GPS + Accelerometer) (if there is a change in position/ movement)
        /** told by yash */
        if ($totalDurationMinute != 0) {
            $avg_speed = $totalDistance / $totalDurationMinute;
            $avg_pace = 60 / $avg_speed;
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            $avg_pace_code = "A";
        }

        if (!$isCompleteButton && $avg_pace == 0) {
            # B) If the user click on the ‘Start’ button, 
            # use phone motion sensor (Accelerometer) 
            # (if there is no change in position/ movement)
            /** told by yash */
            $avg_speed = $totalDistance / $totalDurationMinute;
            $avg_pace = 60 / $avg_speed;
            // $avg_pace = collect($exercises)->whereNotIn('avg_total_pace', ['0', 0, '', null])->pluck('avg_total_pace')->first();
            $avg_pace_code = "B";
        }

        if (!$isCompleteButton && $avg_pace == 0) {
            # C) If the user click on the ‘Start’ button, 
            # record the Average Pace value recorded from the exercise watch (if available).
            $avg_pace_code = "C";
        }

        if ($isCompleteButton && $avg_pace == 0) {
            # D) If the user click on the ‘Complete’ button to log the workout, use equation 
            if ($isPace) {
                # (If user uses ‘Pace’ parameter, please see Average Pace Calculation Guide_Others (NOTE: Stop at ‘Average Pace’ step).
                $avg_pace = $this->calculatePaceCalculationGuidForOTHER($exercises);
            } else if (!$isPace) {
                # If user uses ‘Speed’ parameter, please see Average Speed Calculation Guide to find Average Pace).
                $avg_speed = $this->calculateAverageSpeedGuide_OTHER($exercises);
                $avg_pace = $avg_speed == 0 ? 0 :  60 / $avg_speed;
            }
            $avg_pace_code = "D";
        }

        if ($isCompleteButton && $avg_pace == 0) {
            # E) If the user click on the ‘Complete’ button to log the workout but user did not key in
            # ‘Speed/ Pace’ parameter, Average Pace will show ‘-’.
            $avg_pace_code = "E";
        }
        $avg_pace = $this->convertPaceNumberTo_M_S_format($avg_pace);

        return [
            'avg_pace' => $avg_pace ?? null,
            'avg_pace_unit' => $this->avg_pace_unit ?? null,
            'avg_pace_code' => $avg_pace_code ?? null
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

    /**
     * calculateRPM
     *
     * @param  mixed $trainingLog
     * @return void
     */
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

    /**
     * getAverageOrFirst
     *
     * @param  mixed $exercises
     * @param  mixed $keyName
     * @param  mixed $codeName
     * @return void
     */
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

    /**
     * calculatePower
     *
     * @param  mixed $trainingLog
     * @param  mixed $total_lvl
     * @param  mixed $total_rpm
     * @param  mixed $total_duration_minutes
     * @return void
     */
    public function calculatePower($trainingLog, $total_lvl, $total_rpm, $total_duration_minutes)
    {
        $total_power = 0;
        /** check user which column has selected */
        $isWatt = $trainingLog['exercise'][0]['watt'];

        # "A" Take an average Watt value keyed in the training log 
        # (similar to calculation ‘Lvl’. Please refer to ‘Lvl’ section for the calculation) 
        # (if user use ‘Watt’ in the training log).
        if (isset($isWatt)) {
            $keyName = 'watt';
            $codeName = 'A';
            /** if more then one lap then average else direct take from first lap */
            $wattResponse = $this->getAverageOrFirst($trainingLog['exercise'], $keyName, $codeName);
            $total_power = $wattResponse[$keyName];
            $total_power_code = $wattResponse[$codeName];
        } else {
            # "B" Use RPM & Lvl values and apply the equation 
            # (if user use ‘RPM’ in the training log):
            # (Refer to the RPM and Lvl sections for the calculation)

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
            'total_power' => round($total_power, 2),
            'total_power_unit' => $this->total_power_unit,
            'total_power_code' => $total_power_code
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

        if (is_int($average_power)) {
            # A) Find Relative Power with equation:
            # Relative Power = Average Power / Body weight (in kg)
            $total_relative_power = $average_power / ($userBodyWeight ?? 0);
            $total_relative_power_code = "A";
        } else {
            # B) If Average Power value is not available, Relative Power will show ‘-‘
            $total_relative_power = 0; # replace dash with zero | dash applied from app side
            $total_relative_power_code = "B";
        }

        return [
            'total_relative_power' => round($total_relative_power, 2),
            'total_relative_power_unit' => $this->total_relative_power_unit,
            'total_relative_power_code' => $total_relative_power_code
        ];
    }



    /**
     * calculateTotalKcalBurn => Updated Kcal Calculations. 
     *
     * @param  mixed $trainingLog
     * @param  mixed $usersWeight
     * @param  mixed $total_duration_minutes
     * @return void
     */
    public function calculateTotalKcalBurn($trainingLog, $usersWeight, $total_duration_minutes)
    {
        # A → B

        $total_kcal = 0;
        $isWatt = $trainingLog['exercise'][0]['watt'];
        $isUserMale = $trainingLog['user_detail']['gender'] == GENDER_MALE;
        $isUserFemale = $trainingLog['user_detail']['gender'] == GENDER_FEMALE; // if not $isUserMale => Means "Female". Also there is "Other" Option too.

        $intensity_CODE = $trainingLog['training_intensity']['code'];
        # A) Record the Total kcal value recorded from the exercise watch (if available).
        // $total_kcal_code = 'A';

        if ($total_kcal == 0) {
            # B) Find Total kcal with equation:
            # Step 1) Determine the gender of the user (Male/ Female) and the Intensity in the training log set by the user
            # Step 2) Determine the MET according using the Tables (refer below)
            switch ($intensity_CODE) {
                case TRAINING_INTENSITY_LOW:
                    $mets = $isUserMale ? 2.8 : 2;
                    break;
                case TRAINING_INTENSITY_MODERATELY_LOW:
                    $mets = $isUserMale ? 5 : 3.6;
                    break;
                case TRAINING_INTENSITY_MODERATE:
                    $mets = $isUserMale ? 7 : 5.2;
                    break;
                case TRAINING_INTENSITY_MODERATELY_HIGH:
                    $mets = $isUserMale ? 9 : 6.8;
                    break;
                case TRAINING_INTENSITY_HIGH:
                    $mets = $isUserMale ? 10 : 7.6;
                    break;
            }
            # Step 3) kcal/min = kcal/min = 0.0175 x MET x user’s weight (in kilograms)
            $kcal_min = 0.0175 * $mets * $usersWeight;
            # Step 4) Total kcal = Step 3 x Total Duration in mins
            $total_kcal = $kcal_min *  $total_duration_minutes;
            $total_kcal_code = 'B';
        }
        return [
            // 'total_watt' => $allAddedWatt,
            "total_kcal" => round($total_kcal, 2),
            "total_kcal_code" => $total_kcal_code ?? null
        ];
    }

    /**
     * averageHeartRate
     *
     * @param  mixed $exercises
     * @return void
     */
    public function averageHeartRate($exercises)
    {
        $average_heart_rate = 0;

        # A) Record the Average Heart Rate value recorded from the exercise watch (polar, apple watch, fitbit, garmin). (if available)
        // $average_heart_rate_code = "A";

        if ($average_heart_rate == 0) {
            # B) If user is not using any third party heart rate monitor, Average Heart Rate will show ‘-’.
            $average_heart_rate = 0;
            $average_heart_rate_code = "B";
        }

        return [
            'average_heart_rate' => $average_heart_rate ?? 0,
            'average_heart_rate_code' => $average_heart_rate_code ?? null,
        ];
    }

    /**
     * calculateAveragePower
     *
     * @param  mixed $exercises
     * @return void
     */
    public function calculateAveragePower($exercises)
    {
        # A → B → C
        $average_power = 0;

        $isWatt = isset($exercises[0]['watt']);

        # A) If the user click on the ‘Start’ button, record the Average Power value recorded from the power meter (if available).
        // $average_power_code = "A";

        if ($average_power == 0 && $isWatt) {
            # B) If the user click on the ‘Start’ button OR if the user click on the ‘Complete’ button to log the workout,
            # use equation (if user use ‘Watt’ in the training log):
            # Step 1) Add all the Watt values in the training log to get ‘total watt’.
            # Step 2) Divide the ‘total watt’ by the number of ‘total laps’ in the training log to get the Average Watt

            $addAllWatts = collect($exercises)->sum('watt');
            $average_power = $addAllWatts / count($exercises);
            $average_power_code = "B";
        }
        if ($average_power == 0 && !$isWatt) {
            # C) If user is not using ‘Watt’ in the training log, Average Power will show ‘-‘.
            $average_power = 0;
            $average_power_code = "C";
        }
        return [
            'average_power' => round($average_power, 2) ?? 0,
            'average_power_unit' => $this->average_power_unit  ?? 0,
            'average_power_code' => $average_power_code ?? null,
        ];
    }

    /**
     * calculateLevel
     *
     * @param  mixed $exercises
     * @return void
     */
    public function calculateLevel($exercises)
    {
        $level = 0;
        $isLvl = isset($exercises[0]['lvl']);
        if ($isLvl) {

            # A) Find Lvl with equation:
            # Step 1) Total Lvl = Add all the Lvl value(s) in the training log.
            # Step 2) Lvl = Total Lvl / Total lap(s)
            $addedLvl = collect($exercises)->sum('lvl');
            $level = $addedLvl / count($exercises);
            $level_code = "A";
        } else {
            # B) If user did not key in ‘Lvl’ parameter, Level will show ‘-‘
            $level = 0;
            $level_code = "B";
        }
        return [
            'level' => $level ?? 0,
            'level_code' => $level_code ?? null,
        ];
    }

    /**
     * calculateAverageRPM => average RPM updated 
     *
     * @param  mixed $exercises
     * @return void
     */
    public function calculateAverageRPM($exercises)
    {
        # A → B → C

        $avg_rpm = 0;
        $isRPM = $exercises[0]['rpm'];

        # A) If the user click on the ‘Start’ button, record the Average Power value recorded from the power meter (if available).
        $avg_rpm_code = 'A';

        if ($avg_rpm == 0 && $isRPM) {
            # B) If the user click on the ‘Start’ button OR if the user click on the ‘Complete’ button to log the workout,
            # use equation (if user use ‘RPM’ in the training log):
            # Step 1) Total RPM = Add all the RPM value(s) in the training log.
            # Step 2) RPM = Total RPM / Total lap(s)
            $totalAddRPM = collect($exercises)->sum('rpm');
            $avg_rpm = $totalAddRPM / count($exercises);
            $avg_rpm_code = 'B';
        } elseif ($avg_rpm == 0 && !$isRPM) {
            # C) If user is not using ‘RPM’ in the training log, Average RPM will show ‘-‘.
            $avg_rpm = 0;
            $avg_rpm_code = 'C';
        }
        return [
            'avg_rpm' => $avg_rpm ?? 0,
            'avg_rpm_code' => $avg_rpm_code ?? null,
        ];
    }
}
