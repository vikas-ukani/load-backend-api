<?php

namespace App\Http\Controllers\API\v1\Calender;

use App\Supports\DateConvertor;
use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\Libraries\Repositories\CommonProgramsWeekRepositoryEloquent;
use App\Libraries\Repositories\CommonProgramsWeeksLapsRepositoryEloquent;
use App\Libraries\Repositories\SettingTrainingRepositoryEloquent;
use App\Libraries\Repositories\WeekWiseFrequencyMasterRepositoryEloquent;
use App\Libraries\Repositories\CompletedTrainingProgramRepositoryEloquent;
use App\Libraries\Repositories\PresetTrainingProgramRepositoryEloquent;
use App\Libraries\Repositories\TrainingProgramsRepositoryEloquent;
use App\Libraries\Repositories\WeekWiseWorkoutRepositoryEloquent;
use App\Libraries\Repositories\WorkoutWiseLapRepositoryEloquent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WeekWiseCompletedPrograms extends Controller
{
    use DateConvertor;
    protected $moduleName = "Training program";
    protected $userId;

    protected $workoutWiseLapRepository;
    protected $settingTrainingRepository;
    protected $trainingProgramsRepository;
    protected $weekWiseWorkoutRepository;
    protected $commonProgramsWeekRepository;
    protected $commonProgramsWeeksLapsRepository;
    protected $weekWiseFrequencyMasterRepository;
    protected $completedTrainingProgramRepository;
    protected $presetTrainingProgramRepositoryEloquent;

    public function __construct(
        WorkoutWiseLapRepositoryEloquent $workoutWiseLapRepository,
        SettingTrainingRepositoryEloquent $settingTrainingRepository,
        WeekWiseWorkoutRepositoryEloquent $weekWiseWorkoutRepository,
        TrainingProgramsRepositoryEloquent $trainingProgramsRepository,
        CommonProgramsWeekRepositoryEloquent $commonProgramsWeekRepository,
        WeekWiseFrequencyMasterRepositoryEloquent $weekWiseFrequencyMasterRepository,
        CommonProgramsWeeksLapsRepositoryEloquent $commonProgramsWeeksLapsRepository,
        CompletedTrainingProgramRepositoryEloquent $completedTrainingProgramRepository,
        PresetTrainingProgramRepositoryEloquent $presetTrainingProgramRepositoryEloquent
    ) {
        $this->userId = Auth::id();
        $this->workoutWiseLapRepository = $workoutWiseLapRepository;
        $this->weekWiseWorkoutRepository = $weekWiseWorkoutRepository;
        $this->settingTrainingRepository = $settingTrainingRepository;
        $this->trainingProgramsRepository = $trainingProgramsRepository;
        $this->commonProgramsWeekRepository = $commonProgramsWeekRepository;
        $this->commonProgramsWeeksLapsRepository = $commonProgramsWeeksLapsRepository;
        $this->weekWiseFrequencyMasterRepository = $weekWiseFrequencyMasterRepository;
        $this->completedTrainingProgramRepository = $completedTrainingProgramRepository;
        $this->presetTrainingProgramRepositoryEloquent = $presetTrainingProgramRepositoryEloquent;
    }

    /**
     * createWeekWiseDailyPrograms => create Daily Week wise program to remain complete
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function createWeekWiseDailyPrograms(Request $request)
    {
        $input = $dummyInput = $request->all();

        /** required ( start of the day date ) and ( end of the day date ) of selected date */
        $validation = $this->requiredAllKeysValidation(['program_id', 'start_date', 'end_date', 'week_number', 'workout_number'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }
        /** convert start and end date to UTC format */
        $input['start_date'] = $this->isoToUTCFormat($input['start_date']);
        $input['end_date'] = $this->isoToUTCFormat($input['end_date']);

        # 1. check already exists programs then return
        $todaysProgramExercise = $this->completedTrainingProgramRepository->getDetailsByInput(
            [
                'program_id' => $input['program_id'],
                'start_date' => $input['start_date'],
                'end_date' => $input['end_date'],
                'relation' => [
                    'program_detail',
                    // 'week_wise_workout_detail' => ['week_wise_workout_laps_details']
                    'week_wise_workout_detail'
                ],
                'first' => true
            ]
        );
        /** if exists in current day then return error message " you already exercised today. " */
        if (!!$todaysProgramExercise) {
            $todaysProgramExercise = $todaysProgramExercise->toArray();

            $todaysProgramExercise['week_wise_workout_detail'] = $this->calculateCompletedTHR($todaysProgramExercise['week_wise_workout_detail']);
            $todaysProgramExercise['week_wise_workout_detail']['week_wise_workout_laps_details'] = $this->workoutWiseLapRepository->getDetailsByInput([
                'week_wise_workout_id' => $todaysProgramExercise['week_wise_workout_detail']['id'],
                'sort_by' => ['id', 'asc']
            ])->toArray();

            /** get training program code */
            $presetTrainingProgram = $this->presetTrainingProgramRepositoryEloquent->getDetailsByInput([
                'id' =>  $todaysProgramExercise['program_detail']['preset_training_programs_id'],
                'list' => ['id', 'title', 'code'],
                'first' => true
            ]);
            $dummyInput['program_code'] = $presetTrainingProgram->code;
            $todaysProgramExercise['week_wise_workout_detail']['week_wise_workout_laps_details']
                = $this->callHelperControllerCalculationNew($todaysProgramExercise, $dummyInput);
            return $this->sendSuccessResponse($todaysProgramExercise, __('validation.common.key_already_exist', ['key' => $this->moduleName]));
        }

        $programDetail = $this->trainingProgramsRepository->getDetailsByInput([
            'id' => $input['program_id'],
            'user_id' => $this->userId,
            'relation' => [
                'training_frequency',
                'preset_training_program'
            ],
            'first' => true
        ]);
        if (!!!$programDetail) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }

        $programFrequencyNumber = (int) $programDetail->training_frequency->max_days;
        /** get week number from selected date */
        // use week number here from input
        // $getSelectedWeekFromProgram = $this->getSelectedWeekNumberFromProgram($dummyInput); // OLD

        $trainingProgramKMCode = $programDetail->preset_training_program->code ?? null;
        $getSelectedWeekFromProgram = $input['week_number'];
        /** if not found the get common id from common week table using sequence desc */
        $commonWeekRequest = [
            'training_plan_type' => $trainingProgramKMCode,  // COMMON_PROGRAMS_PLAN_TYPE_10K, // COMMON_PROGRAMS_PLAN_TYPE_5K
            'frequency' => $programFrequencyNumber,
            'week_number' => $getSelectedWeekFromProgram,
            // 'relation' => [
            //  // "week_wise_frequency_master_details" => ["week_wise_workout_laps_details"]
            //     "week_wise_frequency_master_details"
            // ],
            'first' => true
        ];

        $weekWiseFrequency = $this->weekWiseFrequencyMasterRepository->getDetailsByInput($commonWeekRequest);
        /** check if data not found */
        if (!!!$weekWiseFrequency) {
            dd(
                'No Any Program Frequency Workout founds.',
                'training_plan_type',
                $trainingProgramKMCode ?? null,  // COMMON_PROGRAMS_PLAN_TYPE_10K, // COMMON_PROGRAMS_PLAN_TYPE_5K
                'frequency',
                $programFrequencyNumber,
                'week_number',
                $getSelectedWeekFromProgram
            );
        }
        $weekWiseFrequency['week_wise_frequency_master_details'] = $this->weekWiseWorkoutRepository->getDetailsByInput(
            [
                'week_wise_frequency_master_id' => $weekWiseFrequency['id'],
                'list' => ['id', 'workout'],
            ]
        );
        $weekWiseFrequency['week_wise_frequency_master_details'] = $weekWiseFrequency['week_wise_frequency_master_details']->toArray();
        $weekWiseFrequency = $weekWiseFrequency->toArray();
        $dummyInput['program_code'] = $trainingProgramKMCode;

        // dd('check data', --$input['workout_number'], $weekWiseFrequency['workouts'][--$input['workout_number']], $weekWiseFrequency['workouts']);

        if ($trainingProgramKMCode == COMMON_PROGRAMS_PLAN_TYPE_5K)
            $workoutNumberGetFromSequenceNumber = $this->getConditionsWiseWorkoutNumberFor5KOnly($input['week_number'], $programFrequencyNumber, $input['workout_number']);
        else if ($trainingProgramKMCode == COMMON_PROGRAMS_PLAN_TYPE_10K)
            $workoutNumberGetFromSequenceNumber = $this->getConditionsWiseWorkoutNumberFor10KOnly($input['week_number'], $programFrequencyNumber, $input['workout_number']);
        else if ($trainingProgramKMCode == COMMON_PROGRAMS_PLAN_TYPE_21K)
            $workoutNumberGetFromSequenceNumber = $this->getConditionsWiseWorkoutNumberFor21KOnly($input['week_number'], $programFrequencyNumber, $input['workout_number']);
        else if ($trainingProgramKMCode == COMMON_PROGRAMS_PLAN_TYPE_42K)
            $workoutNumberGetFromSequenceNumber = $this->getConditionsWiseWorkoutNumberFor42KOnly($input['week_number'], $programFrequencyNumber, $input['workout_number']);
        else {
            $input['workout_number'] = (int) $input['workout_number'];
            --$input['workout_number'];

            /** check if exists in frequency of workouts */
            if (isset($weekWiseFrequency['workouts'][$input['workout_number']])) {
                # if true then get in workouts
                $workoutNumberGetFromSequenceNumber = (int) $weekWiseFrequency['workouts'][$input['workout_number']];
            } else {
                # else then get in base
                $workoutNumberGetFromSequenceNumber =
                    (int) ($weekWiseFrequency['base']
                        ? (count($weekWiseFrequency['workouts']) + 1)
                        : 1); // if base not found then select first workout  ( NOT ZERO here )
            }
        }

        /** get workout details compare */
        $currentWorkoutData = collect($weekWiseFrequency['week_wise_frequency_master_details'])
            ->where('workout', $workoutNumberGetFromSequenceNumber)
            ->first();

        /** store workout id */
        if (!isset($currentWorkoutData['id'])) {
            Log::error('Workout details not found' . json_encode($currentWorkoutData));
            return $this->sendBadRequest(null, __('validation.common.key_not_found', ['key' => "workout details"]));
        }
        $input['week_wise_workout_id'] = $currentWorkoutData['id'];
        $input['date'] = $this->isoToUTCFormat($dummyInput['start_date']);

        /** create completed training programs */

        $createdProgram = $this->completedTrainingProgramRepository->create($input);
        $createdProgram = $createdProgram->fresh();
        $createdProgram =  $this->makeCustomRelation($createdProgram);
        $createdProgram = $createdProgram->toArray();

        $weekWiseWorkoutLapsDetails = $this->workoutWiseLapRepository->getDetailsByInput([
            'week_wise_workout_id' => $createdProgram['week_wise_workout_detail']['id'],
            'sort_by' => ['id', 'asc']
        ]);
        $createdProgram['week_wise_workout_detail'] = $this->calculateCompletedTHR($createdProgram['week_wise_workout_detail']);
        if (!!$weekWiseWorkoutLapsDetails) {
            $createdProgram['week_wise_workout_detail']['week_wise_workout_laps_details'] = $weekWiseWorkoutLapsDetails->toArray();
            $createdProgram['week_wise_workout_detail']['week_wise_workout_laps_details'] = $this->callHelperControllerCalculationNew($createdProgram, $dummyInput);
        }
        return $this->sendSuccessResponse($createdProgram, __('validation.common.saved', ['module' => $this->moduleName]));
    }

    /**
     * updateWeekWiseDailyPrograms => update exorcizes and is_complete
     *
     * @param  mixed $request
     * @param  mixed $id
     *
     * @return void
     */
    public function updateWeekWiseDailyPrograms(Request $request, $id)
    {
        $input = $request->all();

        # 1. validate exorcizes
        $validation = $this->requiredValidation(['is_complete'/* , 'exercise' */], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        # 1.1 check if id exists
        $existsProgram = $this->completedTrainingProgramRepository->getDetailsByInput(['id' => $id, 'first' => true]);
        if (!!!$existsProgram) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'program detail']));
        }

        # 2. Update Exorcizes
        $updatedProgram = $this->completedTrainingProgramRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($updatedProgram, __('validation.common.saved', ['module' => $this->moduleName]));
    }

    /**
     * calculateCompletedTHR => calculated THR from user`s setting with max HR
     *
     * @param  mixed $weekWiseWorkoutDetail
     *
     * @return void
     */
    public function calculateCompletedTHR($weekWiseWorkoutDetail)
    {
        if (isset($weekWiseWorkoutDetail)) {

            # 1 get hr_max from users setting
            $userSettingTraining = $this->settingTrainingRepository->getDetailsByInput([
                'user_id' => $this->userId,
                'first' => true
            ]);

            /** if hr_max not found then convert from age */
            if (isset($userSettingTraining) && isset($userSettingTraining->hr_max)) {
                $hrMax = $userSettingTraining->hr_max ?? null;
            } else {
                /** get user birth date and convert from year */

                /** IOS Calculation.
                 ** let now = Date().toString(dateFormat: "yyyy")
                 ** let birthday: String = convertDateFormated(date, format: "dd-MM-yyyy", dateFormat: "yyyy")
                 ** let age = Int(now)! - Int(birthday)!
                 * let value = Int(206.9 - (0.67 * Double(age)))
                 * return "\(value)".replace(target: ".00", withString: "")
                 */

                $user = Auth::user();
                $currentYear = $this->getCurrentYear();
                $dobArray = explode('-', $user->date_of_birth);
                $birthYear = end($dobArray);
                $age = (int) $currentYear - (int) $birthYear;

                $hrMax =  (float) (206.9 - (0.67 * (float) ($age)));
                $hrMax = (string) round($hrMax, 1);
            }
            $THR = $weekWiseWorkoutDetail['THR'] ??  null;

            $thrArr = explode('-', $THR);
            $minTHR = ($thrArr[0] / 100)  * $hrMax;
            $higTHR = ($thrArr[1]  / 100) * $hrMax;

            // dd('data ', "ID " . \Auth::id(),  $hrMax, "($thrArr[0] / 100)  * $hrMax",   $thrArr, $minTHR, $higTHR);
            $weekWiseWorkoutDetail['calculated_THR'] = (int) round($minTHR) . "-" . (int) round($higTHR);
        }
        return $weekWiseWorkoutDetail;
    }

    /**
     * getSelectedWeekNumberFromProgram => get selected week number from training program range of weeks
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getSelectedWeekNumberFromProgram($input)
    {
        $program = $this->trainingProgramsRepository->getDetailsByInput([
            'id' => $input['program_id'],
            'list' => ['start_date', 'end_date'],
            'first' => true
        ]);
        $date = $this->isoToUTCFormat($input['start_date']);
        $date = explode(' ', $date);
        $dateRanges = $this->getAllWeeksDatesFromDateRange($program->start_date, $program->end_date, 'Y-m-d');
        foreach ($dateRanges as $index => $dayRange) {
            /** check in array if exists then return */
            if (in_array($date[0],  $dayRange)) {
                $weekCounterNumberIs = $index + 1;
                break;
                // dd('true', $index + 1, $date[0],  $dayRange, $dateRanges);
            }
        }
        // dd('week number ', $date[0],  isset($weekCounterNumberIs) ? $weekCounterNumberIs : count($dateRanges), $dateRanges);
        return isset($weekCounterNumberIs) ? $weekCounterNumberIs : count($dateRanges);
        // dd('date ranges ARE ', $date[0],  $dateRanges);
    }


    /**
     * getConditionsWiseWorkoutNumberFor5KOnly
     *
     * @param  mixed $trainingProgramKMCode
     * @param  mixed $programFrequencyNumber
     * @param  mixed $weekNumber
     *
     * @return void
     */
    public function getConditionsWiseWorkoutNumberFor5KOnly(int $weekNumber, int $programFrequencyNumber, int $workoutNumber)
    {
        if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4 || $programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
            /** check conditions for 4th frequency */
            /**
             * NOTE
             * 1 for WorkOut1 (W1)
             * 2 for WorkOut1 (W2)
             * 3 for WorkOut1 (W3)
             * 4 for Base(B)
             *
             * Use reference from PDF name => "Cardio Training Program - Summary Logs_5k4x.pdf"
             */
            // dd('check week number and workout number', $rangeD, in_array($weekNumber, $rangeD), $weekNumber, $workoutNumber);
            switch ($weekNumber) {
                case in_array($weekNumber, range(1, 6)):
                    $returnWorkoutNumberIs = 1;
                    break;
                case 7:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 8:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 9:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 10:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 11:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 12:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 13:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 14:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 15:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 16:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 17:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 18:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_4) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_FOR_18Week_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_FOR_18Week_returnNumberOfWorkout($workoutNumber);
                    }
                    // $returnWorkoutNumberIs = $this->check_W1_B_W2_B_returnNumberOfWorkout($workoutNumber);
                    break;
            }
        }/*  else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
            # pass
        } */
        return $returnWorkoutNumberIs ?? $workoutNumber;
    }

    /** 10k Custom programs weeks number */
    public function getConditionsWiseWorkoutNumberFor10KOnly(int $weekNumber, int $programFrequencyNumber, int $workoutNumber)
    {
        if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5 || $programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {

            switch ($weekNumber) {
                case in_array($weekNumber, range(1, 6)):
                    $returnWorkoutNumberIs = 1;
                    break;
                case 7:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 8:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 9:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 10:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 11:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 12:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 13:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 14:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 15:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 16:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 17:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 18:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 18:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 19:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 20:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 21:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 22:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 23:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
                case 24:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) {
                        $returnWorkoutNumberIs = $this->check_B_W1_B_B_B_returnNumberOfWorkout($workoutNumber);
                    } else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
                        $returnWorkoutNumberIs = $this->check_B_B_W1_B_B_B_returnNumberOfWorkout($workoutNumber);
                    }
                    break;
            }
        }
        return $returnWorkoutNumberIs ?? $workoutNumber;
    }

    /** 10k Custom programs weeks number */
    public function getConditionsWiseWorkoutNumberFor21KOnly(int $weekNumber, int $programFrequencyNumber, int $workoutNumber)
    {
        if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5 || $programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
            switch ($weekNumber) {
                case in_array($weekNumber, range(1, 6)):
                    $returnWorkoutNumberIs = 1;
                    break;
                case 7:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 8:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 9:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 10:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 11:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 12:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 13:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 14:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 15:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 16:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 17:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 18:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 19:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 20:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 21:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 22:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_B_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_B_W1_B_W2_B_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 23:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 24:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_B_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_B_B_returnNumberOfWorkout($workoutNumber);
                    break;
            }
        }
        return $returnWorkoutNumberIs ?? $workoutNumber;
    }

    /** 10k Custom programs weeks number */
    public function getConditionsWiseWorkoutNumberFor42KOnly(int $weekNumber, int $programFrequencyNumber, int $workoutNumber)
    {
        if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5 || $programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) {
            switch ($weekNumber) {
                case in_array($weekNumber, range(1, 6)):
                    $returnWorkoutNumberIs = 1;
                    break;
                case 7:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 8:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 9:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 10:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 11:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 12:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 13:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 14:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 15:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 16:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_B_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_B_W2_B_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 17:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 18:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 19:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 20:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_W3_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 21:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_B_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_B_W2_B_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 22:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_B_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_B_W2_B_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 23:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_B_W2_B_B_B_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_B_B_W2_B_B_returnNumberOfWorkout($workoutNumber);
                    break;
                case 24:
                    if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_5) $returnWorkoutNumberIs = $this->check_W1_W2_W3_W4_W5_W6_returnNumberOfWorkout($workoutNumber);
                    else if ($programFrequencyNumber == COMMON_PROGRAMS_FREQUENCY_6) $returnWorkoutNumberIs = $this->check_W1_W2_W3_W4_W5_W6_returnNumberOfWorkout($workoutNumber);
                    break;
            }
        }
        return $returnWorkoutNumberIs ?? $workoutNumber;
    }

    /** 5k-4X => 8,  */
    public function check_W1_B_W2_W3_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 4; // if workout is 4 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 3) return 2; // if workout is 2 then switch to workout-2
        elseif ($workoutNumber == 4) return 3; // if workout is 3 then switch to workout-3
    }

    /** 5k-5X => 7, 8,   */
    public function check_W1_B_W2_B_W3_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 4; // if workout is 4 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 3) return 2; // if workout is 2 then switch to workout-2
        elseif ($workoutNumber == 4) return 4; // if workout is 4 then switch to workout-3
        elseif ($workoutNumber == 5) return 3; // if workout is 3 then switch to workout-3
    }

    public function check_W1_B_W2_W3_B_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 4; // if workout is 2 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 3) return 2; // if workout is 3 then switch to workout-2
        elseif ($workoutNumber == 4) return 3; // if workout is 3 then switch to workout-3
        elseif ($workoutNumber == 5) return 4; // if workout is 5 then switch to BASE ( 4 is Base Number)
    }

    public function check_W1_W2_B_W3_B_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1; // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 2; // if workout is 2 then switch to workout-2
        elseif ($workoutNumber == 3) return 4; // if workout is 3 then switch to base ( 4 is Base Number)
        elseif ($workoutNumber == 4) return 3; // if workout is 4 then switch to workout-3
        elseif ($workoutNumber == 5) return 4; // if workout is 5 then switch to base ( 4 is Base Number)
    }

    /** for 18 week only */
    public function check_W1_B_W2_B_W3_FOR_18Week_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 3; // if workout is 2 then switch to BASE ( 3 is Base Number)
        elseif ($workoutNumber == 3) return 2; // if workout is 2 then switch to workout-2
        elseif ($workoutNumber == 4) return 3; // if workout is 2 then switch to BASE ( 3 is Base Number)
    }
    /** use for 18 week of 5K program and 4th week */
    public function check_W1_B_W2_B_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 3; // if workout is 2 then switch to BASE ( 3 is Base Number)
        elseif ($workoutNumber == 3) return 2; // if workout is 2 then switch to workout-2
        elseif ($workoutNumber == 4) return 3; // if workout is 2 then switch to BASE ( 3 is Base Number)
        elseif ($workoutNumber == 5) return 3; // if workout is 5 then switch to BASE ( 3 is Base Number)
    }

    public function check_W1_W2_W3_W4_W5_W6_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 2; // if workout is 2 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 3) return 3; // if workout is 3 then switch to workout-2
        elseif ($workoutNumber == 4) return 4; // if workout is 4 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 5) return 5; // if workout is 5 then switch to workout-3
        elseif ($workoutNumber == 6) return 6; // if workout is 6 then switch to BASE ( 4 is Base Number)
    }

    public function check_W1_B_B_W2_B_B_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 3; // if workout is 2 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 3) return 3; // if workout is 3 then switch to workout-2
        elseif ($workoutNumber == 4) return 2; // if workout is 4 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 5) return 3; // if workout is 5 then switch to workout-3
        elseif ($workoutNumber == 6) return 3; // if workout is 6 then switch to BASE ( 4 is Base Number)
    }

    public function check_W1_B_W2_B_B_B_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 3; // if workout is 2 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 3) return 2; // if workout is 3 then switch to workout-2
        elseif ($workoutNumber == 4) return 3; // if workout is 4 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 5) return 3; // if workout is 5 then switch to workout-3
        elseif ($workoutNumber == 6) return 3; // if workout is 6 then switch to BASE ( 4 is Base Number)
    }

    public function check_B_W1_B_W2_B_B_returnNumberOfWorkout(int $workoutNumber)
    {
        if ($workoutNumber == 1) return 3;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 1; // if workout is 2 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 3) return 3; // if workout is 3 then switch to workout-2
        elseif ($workoutNumber == 4) return 2; // if workout is 4 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 5) return 3; // if workout is 5 then switch to workout-3
        elseif ($workoutNumber == 6) return 3; // if workout is 6 then switch to BASE ( 4 is Base Number)
    }

    public function check_W1_B_W2_B_W3_B_returnNumberOfWorkout(int $workoutNumber)
    {
        /** B = 4 */
        if ($workoutNumber == 1) return 1;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 4; // if workout is 2 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 3) return 2; // if workout is 3 then switch to workout-2
        elseif ($workoutNumber == 4) return 4; // if workout is 4 then switch to BASE ( 4 is Base Number)
        elseif ($workoutNumber == 5) return 3; // if workout is 5 then switch to workout-3
        elseif ($workoutNumber == 6) return 4; // if workout is 6 then switch to BASE ( 4 is Base Number)
    }

    public function check_B_B_W1_B_B_B_returnNumberOfWorkout(int $workoutNumber)
    {
        /** B = 2 */
        if ($workoutNumber == 1) return 2;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 2; // if workout is 2 then switch to BASE ( 2 is Base Number)
        elseif ($workoutNumber == 3) return 1; // if workout is 3 then switch to BASE ( 2 is Base Number)
        elseif ($workoutNumber == 4) return 2; // if workout is 4 then switch to BASE ( 2 is Base Number)
        elseif ($workoutNumber == 5) return 2; // if workout is 5 then switch to BASE ( 2 is Base Number)
        elseif ($workoutNumber == 6) return 2; // if workout is 6 then switch to BASE ( 2 is Base Number)
    }

    public function check_B_W1_B_B_B_returnNumberOfWorkout(int $workoutNumber)
    {
        /** B = 2 */
        if ($workoutNumber == 1) return 2;  // if workout is 1 then switch to workout-1
        elseif ($workoutNumber == 2) return 1; // if workout is 2 then switch to BASE ( 2 is Base Number)
        elseif ($workoutNumber == 3) return 2; // if workout is 3 then switch to BASE ( 2 is Base Number)
        elseif ($workoutNumber == 4) return 2; // if workout is 4 then switch to BASE ( 2 is Base Number)
        elseif ($workoutNumber == 5) return 2; // if workout is 5 then switch to BASE ( 2 is Base Number)
        elseif ($workoutNumber == 6) return 2; // if workout is 6 then switch to BASE ( 2 is Base Number)
    }

    /**
     * makeCustomRelation => make custom relation and make reference it
     *
     * @param  mixed $createdProgram
     *
     * @return void
     */
    public function makeCustomRelation($createdProgram)
    {
        if (isset($createdProgram['program_id'])) {
            $createdProgram['program_detail'] = $this->trainingProgramsRepository->getDetailsByInput([
                'id' => $createdProgram['program_id'],
                'first' => true
            ]);
        }

        if (isset($createdProgram['week_wise_workout_id'])) {
            $createdProgram['week_wise_workout_detail'] = $this->weekWiseWorkoutRepository->getDetailsByInput([
                'id' => $createdProgram['week_wise_workout_id'],
                // 'relation' => ['week_wise_workout_laps_details'],
                'first' => true
            ]);
        }

        if (isset($createdProgram['common_programs_weeks_id'])) {
            $createdProgram['common_programs_weeks_detail'] = $this->commonProgramsWeekRepository->getDetailsByInput([
                'id' => $createdProgram['common_programs_weeks_id'],
                'first' => true
            ]);

            /** get multiple laps from common weeks program laps table */
            $createdProgram['common_programs_weeks_laps_details'] = $this->commonProgramsWeeksLapsRepository->getDetailsByInput([
                'common_programs_week_id' => $createdProgram['common_programs_weeks_detail']['id'],
                'is_active' => true
            ]);
        }
        return $createdProgram;
    }

    /**
     * callHelperControllerCalculationNew => for calculate vdot and speed from helper controller
     *
     * @param  mixed $completedTrainingProgram
     *
     * @return void
     */
    public function callHelperControllerCalculationNew($completedTrainingProgram = null, $dummyInput = null)
    {
        $completedTrainingProgram = !!!is_array($completedTrainingProgram) ?  $completedTrainingProgram->toArray() : $completedTrainingProgram;

        if (isset($completedTrainingProgram) && isset($completedTrainingProgram['week_wise_workout_detail']['week_wise_workout_laps_details'])) {
            $helperController = app(HelperController::class);
            foreach ($completedTrainingProgram['week_wise_workout_detail']['week_wise_workout_laps_details'] as $index => $weeksLaps) {
                $newData = $helperController->calculate_V_DOT($weeksLaps, $dummyInput);
                $newDatas[] = !!!is_array($newData) ?  $newData->toArray() : $newData;
            }
            return $newDatas ?? null;
        }
    }
}
