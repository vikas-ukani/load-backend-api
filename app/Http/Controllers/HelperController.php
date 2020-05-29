<?php

namespace App\Http\Controllers;

use App\Libraries\Repositories\SettingTrainingRepositoryEloquent;

class HelperController extends Controller
{
    protected $userId;
    protected $DIST;
    protected $T;

    protected $VO2;
    protected $PERCENT_MAX;
    protected $vDOT;

    protected $TARGET;
    protected $vdotCode;

    protected $settingTrainingRepository;

    public function __construct(SettingTrainingRepositoryEloquent $settingTrainingRepository)
    {
        $this->userId = \Auth::id();

        // dd('check id', $this->userId);

        $this->settingTrainingRepository = $settingTrainingRepository;
    }

    public function convertKMtoMeters($km = null)/*  () => isset($km) ? ($km * 1000) : null; */
    {
        $this->DIST = isset($km) ? ($km * 1000) : 0;
    }

    public function convertTimeToMinutes($time = null)
    {
        if (isset($time)) {
            $timesArr = explode(':', $time);
            $hr = $timesArr[0] ?? 0;
            $min = $timesArr[1] ?? 0;
            $sec = $timesArr[2] == "00" ? 0 : ((int) $timesArr[2] / 60);
            $totalMinutes = ((((int) ($hr)) ?? 0) * 60)
                + ((((int) ($min)) ?? 0))
                +  $sec;

            $this->T = round($totalMinutes, 2);
        } else {
            $this->T = 0;
        }
    }

    /**
     * * Start Here
     * Calculate vDOT For common_programs_weeks_laps table APP user only
     */
    public function calculate_V_DOT($input = null, $apiRequest = null)
    {
        $settingTrainingDetails = $this->settingTrainingRepository->getDetailsByInput([
            'user_id' => $this->userId,
            'relation' => ["race_distance_detail"],
            'first' => true
        ]);

        /** check for race distance is exists or not */
        if (isset($settingTrainingDetails) && isset($settingTrainingDetails->race_distance_detail)) {
            $kmNumber = explode(" ", $settingTrainingDetails->race_distance_detail->name);

            $this->convertKMtoMeters($kmNumber[0]); // convert KM to METERs
            $this->convertTimeToMinutes($settingTrainingDetails->race_time); // convert Time To Minutes
        }

        # 1. calculate step one and generate VO2
        $this->calculateFirstStepOf_V_DOT();

        # 2. calculate step two and generate PERCENT_MAX
        $this->calculateSecondStepOf_V_DOT();

        # 3. calculate step three and generate V_DOT
        $this->calculateThirdStepOf_V_DOT();

        # 4. calculate step four and speed to condition wise compare with laps vDOT added from admin side in common laps weeks
        $this->calculateFourthStepOf_V_DOT($input, $apiRequest);

        # all Calculation Done HERE
        $oldPace = round($this->TARGET, 2);
        $input['pace'] = $this->convertPaceSeconds($oldPace);
        // dd('check data', $this->TARGET, $oldPace, $input['pace']);

        // dd('check pace ', $input['pace'],  $input['new_pace']);
        $input['speed'] = $this->convertPaceToSpeed($input['pace']);
        $input['vdot_code'] = $this->vdotCode;

        // dd(
        //     'KM = ' . 1/* $kmNumber[0] */ . ' , DIST = ' . $this->DIST . ', TIME in minutes = ' . $this->T,
        //     "",

        //     "Step 1 VO2 => -4.6 + 0.182258 * ($this->DIST / $this->T) + 0.000104 * ($this->DIST / $this->T) * ($this->DIST / $this->T) ",
        //     "ANS of Step 1 => $this->VO2 ",
        //     "",

        //     "Step 2  Percent MAX  => 0.8 + 0.1894393 * exp(-0.012778 * $this->T) + 0.2989558 * exp(-0.1932605 * $this->T)",
        //     "ANS of Step 2 => $this->PERCENT_MAX ",
        //     "",

        //     "Step 3  VDOT => $this->VO2 / $this->PERCENT_MAX",
        //     "ANS of Step 3 => $this->vDOT ",
        //     "",

        //     "Step 4 TARGET => ( 1000 * 2 * 0.000104) / (-0.182258 + sqrt( (0.182258 * 0.182258) - 4 * 0.000104 * (-4.6 - 0.84 * $this->vDOT) ))",
        //     "ANS of Step 4 => " . round($this->TARGET, 2),
        //     "",

        //     "Final Pace " . $input['pace'],
        //     "Final Speed " . $input['speed'],
        //     "",

        //     "Input Laps are",
        //     $input,
        //     $apiRequest
        // );
        return $input;
    }

    public function convertPaceSeconds($pace)
    {
        # pass
        // dd('pace', $pace);
        $paceArr = explode('.', $pace);
        if (isset($paceArr) && isset($paceArr[1])) {
            // $seconds = (float) ('0.' . $paceArr[1]) * 60;
            $seconds = ('0.' . $paceArr[1]) * 60;
            // dd('second ', $seconds);
            $secondArr = explode('.', $seconds);
            // dd('s arra', $paceArr, strval($seconds), $secondArr);

            if (isset($secondArr)) {
                $secondArr[1] = isset($secondArr[1]) ? $secondArr[1] : 0;
                $secondArr[0] += ($secondArr[1] >= 6 ? 1 : 0);
                // dd('check s', $secondArr[1]);
                $newPace = $paceArr[0] . ":" . ((strlen($secondArr[0]) == 2) ? $secondArr[0] : "0" . $secondArr[0]);
                // dd('new pace', $newPace);
                return $newPace;
            }
        }
    }

    /**
     ** Step => 1
     *
     * calculateFirstStepOf_V_DOT
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function calculateFirstStepOf_V_DOT($input = null)
    {
        $data = ($this->DIST / $this->T)
            * ($this->DIST / $this->T);
        $this->VO2 =
            -4.6 + 0.182258 * ($this->DIST / $this->T) + 0.000104 * ($this->DIST / $this->T) * ($this->DIST / $this->T);

        $this->VO2 = round($this->VO2, 2);
        // dd('check data', $this->VO2);
    }

    /**
     ** Step => 2
     *
     * calculateSecondStepOf_V_DOT
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function calculateSecondStepOf_V_DOT($input = null)
    {
        $this->PERCENT_MAX = 0.8 + 0.1894393
            * exp(-0.012778 * $this->T)
            + 0.2989558
            * exp(-0.1932605 * $this->T);
    }

    /**
     ** Step => 3
     *
     * calculateThirdStepOf_V_DOT => ( Step 1 / Step 2 )
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function calculateThirdStepOf_V_DOT($input = null)
    {
        $this->vDOT = $this->VO2 / $this->PERCENT_MAX;
        $this->vDOT = round($this->vDOT, 2);
    }

    /**
     ** Step => 4
     *
     * calculateFourthStepOf_V_DOT =>
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function calculateFourthStepOf_V_DOT($input = null, $apiRequest)
    {
        // dd('check', $input, $apiRequest);
        $input = !!!is_array($input) ? $input : $input;
        /** check for "E/M" and calculate condition week wise wise vdot */
        if (isset($input['VDOT']) && $input['VDOT'] == "E/M" && $apiRequest['program_code'] == COMMON_PROGRAMS_PLAN_TYPE_5K) {
            $this->calculateEorMConditionWiseFor5KOnly($apiRequest);
        } elseif (stripos($input['VDOT'], 'E') !== false) {
            $this->calculateForEvDot();
            // $this->calculateForI5KvDot();
        } else if (stripos($input['VDOT'], 'M') !== false) {
            $this->calculateForMvDot();
        } else  if (stripos($input['VDOT'], 'I-5k') !== false) {
            $this->calculateForI5KvDot();
        } else  if (stripos($input['VDOT'], 'I') !== false) {
            $this->calculateForIvDot();
        } else  if (stripos($input['VDOT'], 'R') !== false) {
            $this->calculateForRvDot();
        } else  if (stripos($input['VDOT'], 'T') !== false) {
            $this->calculateForTvDot();
        }
    }

    public function calculateEorMConditionWiseFor5KOnly($apiRequest)
    {
        if (isset($apiRequest['week_number']) && ($apiRequest['week_number'] == 1 || $apiRequest['week_number'] == 2)) {
            $this->calculateForEvDot(); // E
        } else if (isset($apiRequest['week_number']) && $apiRequest['week_number'] == 3) {
            if ($apiRequest['workout_number'] == 1) return $this->calculateForEvDot(); // E
            else if ($apiRequest['workout_number'] == 2) return $this->calculateForMvDot(); // M
            else if ($apiRequest['workout_number'] == 3)  return $this->calculateForEvDot(); // E
            else if ($apiRequest['workout_number'] == 4) return $this->calculateForMvDot(); // M
        } else if (isset($apiRequest['week_number']) && $apiRequest['week_number'] == 4) {
            if ($apiRequest['workout_number'] == 1) return $this->calculateForMvDot(); // M
            else if ($apiRequest['workout_number'] == 2) return $this->calculateForMvDot(); // M
            else if ($apiRequest['workout_number'] == 3)  return $this->calculateForEvDot(); // E
            else if ($apiRequest['workout_number'] == 4) return $this->calculateForMvDot(); // M
        } else if (isset($apiRequest['week_number']) && ($apiRequest['week_number'] == 5 || $apiRequest['week_number'] == 6)) {
            $this->calculateForMvDot(); // M
        }
    }

    /**
     * calculateForTvDot => Calculate E
     *
     * @return void
     */
    public function calculateForEvDot()
    {
        $this->vdotCode = "E";
        // (X * 2 * 0.000104) / ( -0.182258 + SQRT( 0.182258 ^ 2 - 4 * 0.000104 * ( -4.6 - 0.67 * Y)))
        $this->TARGET = (/* $this->DIST */1000 * 2 * 0.000104)
            / (-0.182258
                +
                sqrt(
                    (0.182258 * 0.182258) - 4 * 0.000104 * (-4.6 - 0.67 * $this->vDOT)
                ));
    }

    /**
     * calculateForTvDot => Calculate M
     *
     * @return void
     */
    public function calculateForMvDot()
    {
        $this->vdotCode = "M";
        // (X * 2 * 0.000104) / (-0.182258 + SQRT(0.182258 ^ 2 - 4 * 0.000104 * (-4.6 - 0.80 * Y)));
        $this->TARGET = (/* $this->DIST */1000 * 2 * 0.000104)
            / (-0.182258
                +
                sqrt(
                    (0.182258 * 0.182258) - 4 * 0.000104 * (-4.6 - 0.80 * $this->vDOT)
                ));
    }

    /**
     * calculateForTvDot => Calculate I
     *
     * @return void
     */
    public function calculateForIvDot()
    {
        $this->vdotCode = "I";
        // (X * 2 * 0.000104) / (-0.182258 + SQRT(0.182258 ^ 2 - 4 * 0.000104 * (-4.6 - 0.975 * Y)))
        $this->TARGET = (/* $this->DIST */1000 * 2 * 0.000104)
            / (-0.182258
                +
                sqrt(
                    (0.182258 * 0.182258) - 4 * 0.000104 * (-4.6 - 0.975 * $this->vDOT)
                ));
    }

    /**
     * calculateForI5KvDot => Calculate I-5K
     *
     * @return void
     */
    public function calculateForI5KvDot()
    {
        $this->vdotCode = "I-5K";

        // (X * 2 * 0.000104) / (-0.182258 + SQRT(0.182258 ^ 2 - 4 * 0.000104 * (-4.6 - 0.84 * Y)));
        $this->TARGET = (/* $this->DIST */1000 * 2 * 0.000104)
            / (-0.182258
                +
                sqrt(
                    (0.182258 * 0.182258) - 4 * 0.000104 * (-4.6 - 0.84 * $this->vDOT)
                ));
    }

    /**
     * calculateForTvDot => Calculate R
     *
     * @return void
     */
    public function calculateForRvDot()
    {
        $this->vdotCode = "R";
        // (X * 2 * 0.000104) / (-0.182258 + SQRT(0.182258 ^ 2 - 4 * 0.000104 * (-4.6 - 1.1 * Y)))
        $this->TARGET = (/* $this->DIST */1000 * 2 * 0.000104)
            / (-0.182258
                +
                sqrt(
                    (0.182258 * 0.182258) - 4 * 0.000104 * (-4.6 - 1.1 * $this->vDOT)
                ));
    }

    /**
     * calculateForTvDot => Calculate T
     *
     * @return void
     */
    public function calculateForTvDot()
    {
        $this->vdotCode = "T";
        // (X*2*0.000104)/(-0.182258+SQRT(0.182258^2-4*0.000104*(-4.6-0.88*Y)))
        $this->TARGET = (/* $this->DIST */1000 * 2 * 0.000104)
            / (-0.182258
                +
                sqrt(
                    (0.182258 * 0.182258) - 4 * 0.000104 * (-4.6 - 0.88 * $this->vDOT)
                ));
                
    }

    /**
     * convertPaceToSpeed => convert pace to speed
     *
     * $pace maybe "7.09"
     * @param  mixed $pace
     *
     * @return void
     */
    public function convertPaceToSpeed($pace)
    {
        // New 
        if (isset($pace)) {
            if (strstr($pace, ':') !== false) {
                $paceArray = explode(":", $pace);
            } else if (strstr($pace, '.') !== false) {
                $paceArray = explode(".", $pace);
            }

            $seconds = (int) ($paceArray[0]) * 60
                + (int) ($paceArray[1]);
            $speed = 3600 / $seconds;
            // dd('speed of pace are', $seconds,  $speed, $pace, $paceArray,  strval(round($speed, 1)));
            return strval(round($speed, 1));
            dd('speed of pace are', $seconds,  $speed, $pace, $paceArray);
        } else {
            return $pace;
        }
        // 
        // OLD 
        // if (isset($pace)) {
        //     $paceArray = explode(".", $pace);
        //     $seconds = (int) ($paceArray[0]) * 60
        //         + (int) ($paceArray[1]);
        //     $speed = 3600 / $seconds;
        //     return $speed;
        //     // dd('speed of pace are', $seconds,  $speed, $pace, $paceArray);
        // } else {
        //     return $pace;
        // }
    }
}
