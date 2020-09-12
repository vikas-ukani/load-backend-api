<?php

namespace App\Http\Controllers\API\v1\Calender;

use Illuminate\Http\Request;
use App\Supports\DateConvertor;
use App\Models\TrainingPrograms;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\Libraries\Repositories\WorkoutWiseLapRepositoryEloquent;
use App\Libraries\Repositories\WeekWiseWorkoutRepositoryEloquent;
use App\Libraries\Repositories\TrainingProgramsRepositoryEloquent;
use App\Libraries\Repositories\CommonProgramsWeekRepositoryEloquent;
use App\Libraries\Repositories\CommonProgramsWeeksLapsRepositoryEloquent;
use App\Libraries\Repositories\WeekWiseFrequencyMasterRepositoryEloquent;
use App\Libraries\Repositories\CompletedTrainingProgramRepositoryEloquent;
use App\Libraries\Repositories\SettingTrainingRepositoryEloquent;

class TrainingProgramController1 extends Controller
{
    use DateConvertor;

    protected $moduleName = "Training program";

    protected $workoutWiseLapRepository;
    protected $weekWiseWorkoutRepository;
    protected $settingTrainingRepository;
    protected $trainingProgramsRepository;
    protected $commonProgramsWeekRepository;
    protected $commonProgramsWeeksLapsRepository;
    protected $weekWiseFrequencyMasterRepository;
    protected $completedTrainingProgramRepository;

    /**
     * __construct => Repository Injection
     *
     * @param  mixed $trainingProgramsRepository
     *
     * @return void
     */
    public function __construct(
        WorkoutWiseLapRepositoryEloquent $workoutWiseLapRepository,
        SettingTrainingRepositoryEloquent $settingTrainingRepository,
        WeekWiseWorkoutRepositoryEloquent $weekWiseWorkoutRepository,
        TrainingProgramsRepositoryEloquent $trainingProgramsRepository,
        CommonProgramsWeekRepositoryEloquent $commonProgramsWeekRepository,
        WeekWiseFrequencyMasterRepositoryEloquent $weekWiseFrequencyMasterRepository,
        CommonProgramsWeeksLapsRepositoryEloquent $commonProgramsWeeksLapsRepository,
        CompletedTrainingProgramRepositoryEloquent $completedTrainingProgramRepository
    ) {
        $this->workoutWiseLapRepository = $workoutWiseLapRepository;
        $this->weekWiseWorkoutRepository = $weekWiseWorkoutRepository;
        $this->settingTrainingRepository = $settingTrainingRepository;
        $this->trainingProgramsRepository = $trainingProgramsRepository;
        $this->commonProgramsWeekRepository = $commonProgramsWeekRepository;
        $this->commonProgramsWeeksLapsRepository = $commonProgramsWeeksLapsRepository;
        $this->weekWiseFrequencyMasterRepository = $weekWiseFrequencyMasterRepository;
        $this->completedTrainingProgramRepository = $completedTrainingProgramRepository;
    }

    /**
     * store => create training program
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Request $request)
    {
        $input = $request->all();
        /** check status and type validation */
        $validation = $this->requiredValidation(['status', 'type'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        /** RESISTANCE and PRESET */
        if ($input['status'] === TRAINING_PROGRAM_STATUS_RESISTANCE && $input['type'] === TRAINING_PROGRAM_TYPE_PRESET) {
            $response = $this->storeResistanceAndCardioPresetFn($input);
            if (isset($response) && $response['flag'] == false) {
                return $this->sendBadRequest(null, $response['message']);
            }
            return $this->sendSuccessResponse($response['data'], $response['message']);
            /** RESISTANCE and CUSTOM */ // FIXME Remain to store
        } elseif ($input['status'] == TRAINING_PROGRAM_STATUS_RESISTANCE && $input['type'] == TRAINING_PROGRAM_TYPE_CUSTOM) {
            $response = $this->storeResistanceCustomFn($input);
            if (isset($response) && $response['flag'] == false) {
                return $this->sendBadRequest(null, $response['message']);
            }
            return $this->sendSuccessResponse($response['data'], $response['message']);
            /** CARDIO and PRESET */
        } elseif ($input['status'] == TRAINING_PROGRAM_STATUS_CARDIO && $input['type'] == TRAINING_PROGRAM_TYPE_PRESET) {
            $response = $this->storeResistanceAndCardioPresetFn($input);
            if (isset($response) && $response['flag'] == false) {
                return $this->sendBadRequest(null, $response['message']);
            }
            return $this->sendSuccessResponse($response['data'], $response['message']);

            /** CARDIO and CUSTOM */ // FIXME Remain to store
        } elseif ($input['status'] == TRAINING_PROGRAM_STATUS_CARDIO && $input['type'] == TRAINING_PROGRAM_TYPE_CUSTOM) {
            # code...
        }

        /** if status and type was not found */
        return $this->sendBadRequest(null, __("validation.common.invalid_key1_key2", ["key1" => "status", "key2" => "type"]));
    }

    /**
     * storeResistanceAndCardioPresetFn => to create training program with status type
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function storeResistanceAndCardioPresetFn($input)
    {
        /** validation */
        $validator = TrainingPrograms::validation($input);
        if ($validator->fails()) {
            return $this->makeError(null, $validator->errors()->first());
        }
        $input['start_date'] = $this->isoToUTCFormat($input['start_date']);
        $input['end_date'] = $this->isoToUTCFormat($input['end_date']);
        // $input['date'] = $this->isoToUTCFormat($input['date']); // no need to convert UTC bcz converted in from model
        $createdTraining = $this->trainingProgramsRepository->create($input);
        $createdTraining = $createdTraining->fresh();
        return $this->makeResponse($createdTraining, __("validation.common.created", ["module" => $this->moduleName]));
    }

    /**
     * storeResistanceCustomFn => create resistance with custom program
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function storeResistanceCustomFn($input = null)
    {
        /** validation */
        $validator =  TrainingPrograms::validation($input);
        if ($validator->fails()) {
            return $this->makeError(null, $validator->errors()->first());
        }

        /** store data */
        $trainingProgram = $this->trainingProgramsRepository->create($input);
        $trainingProgram = $trainingProgram->fresh();

        return $this->makeResponse($trainingProgram, __("validation.common.saved", ["module" => $this->moduleName]));
    }

    public function createDailyProgram(Request $request)
    {
        $input = $dummyInput = $request->all();

        /** required ( start of the day date ) and ( end of the day date ) of selected date */
        $validation = $this->requiredAllKeysValidation(['program_id', 'start_date', 'end_date'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }
        /** convert start and end date to UTC format */
        $input['start_date'] = $this->isoToUTCFormat($input['start_date']);
        $input['end_date'] = $this->isoToUTCFormat($input['end_date']);

        # 1. check already exists programs
        $todaysProgramExercise = $this->completedTrainingProgramRepository->getDetailsByInput(
            [
                'program_id' => $input['program_id'],
                'start_date' => $input['start_date'],
                'end_date' => $input['end_date'],
                'relation' => ['common_programs_weeks_detail', 'program_detail', 'common_programs_weeks_laps_details'],
                'first' => true
            ]
        );
        /** if exists in current day then return error message  " you already exercised today. " */
        if (!!$todaysProgramExercise) {
            $todaysProgramExercise = $todaysProgramExercise->toArray();
            $todaysProgramExercise['common_programs_weeks_laps_details'] = $this->callHelperControllerCalculation($todaysProgramExercise);
            return $this->sendSuccessResponse($todaysProgramExercise, __('validation.common.key_already_exist', ['key' => $this->moduleName]));
        }

        /** get week number from selected date */
        $getSelectedWeekFromProgram = $this->getSelectedWeekNumberFromProgram($dummyInput);
        /** if not found the get common id from common week table using sequence desc */
        $commonWeekRequest = [
            'sequence' => $getSelectedWeekFromProgram,
            'sort_by' => ['sequence', 'asc'],
            'first' => true
        ];
        $commonProgramWeek = $this->commonProgramsWeekRepository->getDetailsByInput($commonWeekRequest);
        if (isset($commonProgramWeek)) {
            $input['common_programs_weeks_id'] = $commonProgramWeek->id;
        } else {
            /** if not found then set last sequence id of all weeks  */
            $commonWeekRequest = [
                'sort_by' => ['sequence', 'desc'],
                'first' => true
            ];
            $commonProgramWeek = $this->commonProgramsWeekRepository->getDetailsByInput($commonWeekRequest);
            $input['common_programs_weeks_id'] = $commonProgramWeek->id;
        }
        //     }
        // }
        $input['date'] = $this->isoToUTCFormat($dummyInput['start_date']);
        $createdProgram = $this->completedTrainingProgramRepository->create($input);
        $createdProgram = $createdProgram->fresh();
        $createdProgram =  $this->makeCustomRelation($createdProgram);
        // $createdProgram = $createdProgram->toArray();

        $createdProgram['common_programs_weeks_laps_details'] = $this->callHelperControllerCalculation($createdProgram->toArray());

        return $this->sendSuccessResponse($createdProgram, __('validation.common.saved', ['module' => $this->moduleName]));
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
     * callHelperControllerCalculation => for calculate vdot and speed from helper controller
     *
     * @param  mixed $completedTrainingProgram
     *
     * @return void
     */
    public function callHelperControllerCalculation($completedTrainingProgram = null)
    {
        $completedTrainingProgram = !!!is_array($completedTrainingProgram) ?  $completedTrainingProgram->toArray() : $completedTrainingProgram;

        if (isset($completedTrainingProgram) && isset($completedTrainingProgram['common_programs_weeks_laps_details'])) {
            $helperController = app(HelperController::class);
            foreach ($completedTrainingProgram['common_programs_weeks_laps_details'] as $index => $weeksLaps) {
                // dd('check data', $completedTrainingProgram->common_programs_weeks_laps_details->toArray());
                $newData = $helperController->calculate_V_DOT($weeksLaps);
                $newDatas[] = !!!is_array($newData) ?  $newData->toArray() : $newData;
                // $weeksLaps = $helperController->calculate_V_DOT($weeksLaps);
            }
            return $newDatas ?? null;
        }
    }


    /**
     * updateDailyProgram => update exorcizes and is_complete
     *
     * @param  mixed $request
     * @param  mixed $id
     *
     * @return void
     */
    public function updateDailyProgram(Request $request, $id)
    {
        $input = $request->all();

        #1. validate exorcizes
        $validation = $this->requiredValidation(['is_complete'/* , 'exercise' */], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        #1.1 Check Exorcizes is current date is today or not

        #2. Update Exorcizes
        $updatedProgram = $this->completedTrainingProgramRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($updatedProgram, __('validation.common.saved', ['module' => $this->moduleName]));
    }


    /**
     * checkProgramIsAvailableOrNotToStore => check for programs is available or not
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function checkProgramIsAvailableOrNotToStore(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['start_date'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        /** check date wise preset program is found or not */
        $trainingProgramCount = $this->trainingProgramsRepository->getDetailsByInput(
            [
                'user_id' => Auth::id(),
                // "status" => $input['status'],
                'type' => TRAINING_PROGRAM_TYPE_PRESET,
                'start_date' => $input['start_date'],
                'end_date' => $input['end_date'],
                'first' => true,
                'is_count' => true,
            ]
        );

        /** check if data found then show error */
        if (isset($trainingProgramCount) &&  $trainingProgramCount > 0) {
            return $this->sendBadRequest(null, __('validation.common.can_not_create_program_to_this_date'));
        } else {
            return $this->sendSuccessResponse(null, "");
        }
    }
}
