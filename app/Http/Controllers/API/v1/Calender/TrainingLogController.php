<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\Calender;

use App\Models\TrainingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Repositories\TrainingLogRepositoryEloquent;
use App\Libraries\Repositories\SavedWorkoutsRepositoryEloquent;
use App\Libraries\Repositories\TrainingProgramsRepositoryEloquent;
use App\Libraries\Repositories\LogCardioValidationsRepositoryEloquent;
use App\Libraries\Repositories\TrainingActivityRepositoryEloquent;
use App\Http\Controllers\API\v1\Calender\LogSummary\CycleCalculationsController;
use App\Http\Controllers\API\v1\Calender\LogSummary\SummaryCalculationController;

class TrainingLogController extends Controller
{
    protected $moduleName = "Training Log";
    protected $savedTemplateModuleName = "Template";

    protected $trainingLogRepository;
    protected $savedWorkoutsRepository;
    protected $trainingProgramsRepository;
    protected $trainingActivityRepository;
    protected $logCardioValidationsRepository;

    public function __construct(
        TrainingLogRepositoryEloquent $trainingLogRepository,
        SavedWorkoutsRepositoryEloquent $savedWorkoutsRepository,
        TrainingProgramsRepositoryEloquent $trainingProgramsRepository,
        TrainingActivityRepositoryEloquent $trainingActivityRepository,
        LogCardioValidationsRepositoryEloquent $logCardioValidationsRepository
    ) {
        $this->trainingLogRepository = $trainingLogRepository;
        $this->savedWorkoutsRepository = $savedWorkoutsRepository;
        $this->trainingProgramsRepository = $trainingProgramsRepository;
        $this->trainingActivityRepository = $trainingActivityRepository;
        $this->logCardioValidationsRepository = $logCardioValidationsRepository;
    }

    /**
     * saveLogFlag => update training log status is_log to false
     *
     * @param mixed $id
     *
     * @return void
     */
    public function saveLogFlag($id)
    {
        $trainingLog = $this->trainingLogRepository->getDetailsByInput(['id' => $id, 'first' => true]);
        if (!isset($trainingLog)) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $trainingLog->is_log = false;
        $trainingLog->save();

        /** FIXME  Do calculate for */
        // $this->doCalculateExercise($trainingLog);

        $trainingLog = $trainingLog->fresh();
        return $this->sendSuccessResponse($trainingLog, __("validation.common.saved", ["module" => $this->moduleName]));
    }

    /**
     * saveToTemplateAsSavedWorkout => create training log to template and show in saved workout,
     *
     * @param mixed $id
     *
     * @return void
     */
    public function saveToTemplateAsSavedWorkout($id)
    {
        $trainingLog = $this->trainingLogRepository->getDetailsByInput([
            'id' => $id,
            'first' => true,
        ]);
        if (!isset($trainingLog)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        $trainingLog->is_log = false; // set to false means show for both log and template
        $trainingLog->save();
        $template = $this->saveWorkoutAsTemplate($trainingLog);
        if (isset($template) && $template['flag'] == false) {
            return $this->sendBadRequest(null, $template['message']);
        }
        return $this->sendSuccessResponse($template['data'], __("validation.common.created", ["module" => $this->moduleName]));
    }

    /**
     * saveWorkoutAsTemplate => to save workout template
     *
     * @param mixed $trainingLog
     *
     * @return void
     */
    public function saveWorkoutAsTemplate($trainingLog)
    {
        try {
            /** first check template is already exists then update else create new template */
            $savedTemplateIs = $this->savedWorkoutsRepository->getDetailsByInput([
                'training_log_id' => $trainingLog->id,
                'user_id' => $trainingLog->user_id,
                'first' => true
            ]);

            /** if template was found then update it else create new */
            if (isset($savedTemplateIs)) {
                $savedWorkout = $this->savedWorkoutsRepository->updateRich([
                    'training_log_id' => $trainingLog->id,
                    'user_id' => $trainingLog->user_id,
                ], $savedTemplateIs->id);
            } else {
                $savedWorkout = $this->savedWorkoutsRepository->create([
                    'training_log_id' => $trainingLog->id,
                    'user_id' => $trainingLog->user_id,
                ]);
            }
            $savedWorkout = $savedWorkout->fresh();
            return $this->makeResponse($savedWorkout, __("validation.common.saved", ["module" => $this->savedTemplateModuleName]));
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            /** if any error found then remove first training log records */
            $this->trainingLogRepository->delete($trainingLog->id);
            return $this->makeError(null, $exception->getMessage());
        }
    }

    // function getPace()
    // {
    //     $dis_pace = $this->distance / 1000;

    //     //getting seconds per km
    //    $pace = $this->total_time / $dis_pace;

    //     //getting minutes from $pace
    //     $min = floor($pace / 60);

    //     //adding 0 before,  if lower than 10
    //     $min = ($min > 10) ? $min : '0' . $min;

    //     //getting remaining seconds
    //     $sec = $pace % 60;

    //     //adding 0 before, if lower than 10
    //     $sec = ($sec > 10) ? $sec : '0' . $sec;

    //     return $min . ":" . $sec;
    // }


    /** create Training log
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function createTrainingLog(Request $request)
    {
        $input = $request->all();

        /** validation for training log details */
        $validator = TrainingLog::validation($input);
        if ($validator->fails()) {
            return $this->sendBadRequest(null, $validator->errors()->first());
        }

        /** check for CARDIO Custom validations. */
        if ($input['status'] == TRAINING_LOG_STATUS_CARDIO) {
            if (!isset($input['training_activity_id'])) {
                return $this->sendBadRequest(null, __("validation.common.select_any_value", ['module' => 'activity']));
            }
            /** check validation for swimming style key while activity is swimming */
            $swimming = $this->trainingActivityRepository->getDetailsByInput([
                'search' => "swimming",
                'list' => ['id'],
                'first' => true
            ]);
            if ($swimming->id == $input['training_activity_id'] && !isset($input['training_log_style_id'])) {
                return $this->sendBadRequest(null, __("validation.common.select_any_value", ['module' => 'swimming style']));
            }
        }

        /** convert date time to utc format */
        $date = $input['date'];

        // $input['date'] = $this->utcToDateTimeFormat($input['date']);
        // $input['date'] = $this->isoToUTCFormat($input['date']);

        // dd('date converting', "Incoming Request " . $date, " Converted date " . $input['date']);

        /** create training log */
        $trainingLog = $this->trainingLogRepository->create($input);
        $trainingLog = $trainingLog->fresh();

        /** from app first set flag for save and then click on logit button with save template flag then after save workout template */
        /** when user save template then store log into saved_workout table*/
        if (isset($input['is_saved_workout']) && $input['is_saved_workout'] == true) {
            $trainingLog->is_log = false; // set to false means show for both log and template
            $trainingLog->save();
            $template = $this->saveWorkoutAsTemplate($trainingLog);
            if (isset($template) && $template['flag'] == false) {
                return $this->sendBadRequest(null, $template['message']);
            }
        }

        /** give return data with relation */
        $returnDetailsRequest = [
            'id' => $trainingLog->id,
            'relation' => ["user_detail", "training_activity", "training_goal", "training_intensity"],
            'first' => true
        ];
        $trainingLog = $this->trainingLogRepository->getDetailsByInput($returnDetailsRequest);
        $trainingLog = $trainingLog->toArray();

        /** store response in json file in public directory */
        // try {
        //     $data = [
        //         'request' => $input,
        //         'response' => $trainingLog
        //     ];
        //     $this->storeRequestAndResponse($data, $this->moduleName, 'req-res.json');
        //     // Storage::put(
        //     //     'data/' . str_replace(' ', '', $this->moduleName) . '/req-res.json',
        //     //     json_encode($data)
        //     //     // json_encode($data, )
        //     // );
        // } catch (\Exception $th) {
        // }
        return $this->sendSuccessResponse($trainingLog, __("validation.common.created", ["module" => $this->moduleName]));
    }

    public function show($id)
    {
        /** give return data with relation */
        $trainingLog = $this->trainingLogRepository->getDetailsByInput(
            [
                'id' => $id,
                'relation' => ["user_detail", "training_activity", "training_goal", "training_intensity"],
                'user_detail_list' => ['id', 'name', 'photo', 'country_id'],
                'training_activity_list' => ['id', 'name', 'icon_path', 'icon_path_red', 'is_active'],
                'training_goal_list' => ['id', 'name', 'is_active'],
                'training_intensity_list' => ['id', 'name', 'is_active'],
                'first' => true
            ]
        );
        if (!isset($trainingLog)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($trainingLog, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * trainingLogListing => get Training Listing
     *
     * @param mixed $request
     *
     * @return void
     */
    public function trainingLogListing(Request $request)
    {
        $input = $request->all();
        /** check for date range */
        if (isset($input['start_date'])) {
            /** convert date to UTC formate from ISOdate */
            $input['start_date'] = $this->isoToUTCFormat($input['start_date']);
        }
        if (isset($input['end_dae'])) {
            $input['end_date'] = $this->isoToUTCFormat($input['end_date']);
        }
        $trainingLogs = $this->trainingLogRepository->getDetails($input);

        if (isset($trainingLogs) && $trainingLogs['count'] == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }

        return $this->sendSuccessResponse($trainingLogs, __("validation.common.details_found", ['module' => $this->moduleName]));
    }

    public function getTrainingProgramAndLog(Request $request)
    {
        $input = $request->all();

        /** check for date range */
        if (isset($input['start_date'])) {
            /** convert date to UTC formate from ISOdate */
            $input['start_date'] = $this->isoToUTCFormat($input['start_date']);
        }
        if (isset($input['end_dae'])) {
            $input['end_date'] = $this->isoToUTCFormat($input['end_date']);
        }

        $logInput = $input;
        $data['training_log_list'] = $this->trainingLogRepository->getDetails($logInput);

        $programInput = $input;
        /** remove relation for program */
        unset($programInput['relation']);
        $programInput['relation'] = ["user_detail", "training_frequency", "preset_training_program"];
        // $programInput['current_month'] = $this->getCurrentMonthByGivenDate($input['start_date']);
        $data['training_program_list'] = $this->trainingProgramsRepository->getDetails($programInput);

        if ($data['training_log_list']['count'] == 0 && $data['training_program_list']['count'] == 0)
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Calender"]));
        $data = [
            'training_log_list' => $data['training_log_list']['list'] ?? [],
            'training_program_list' => $data['training_program_list']['list'] ?? []
        ];
        return $this->sendSuccessResponse($data, __('validation.common.details_found', ['module' => "Calender"]));
    }

    /**
     * destroy => delete training log records
     *
     * @param mixed $trainingLog
     *
     * @return void
     */
    public function destroy($id)
    {
        try {
            $trainingLog = $this->trainingLogRepository->getDetailsByInput([
                'id' => $id,
                'first' => true
            ]);
            /** check if data found then delete them */
            if (!isset($trainingLog)) {
                return $this->sendBadRequest(null, __("validation.common.details_not_found", ["module" => $this->moduleName]));
            }
            // $trainingLog->delete();

            /** check for log is exist in saved workout as a templates */
            $savedTemplate = $this->savedWorkoutsRepository->getDetailsByInput(['training_log_id' => $id, 'user_id' => Auth::id(), 'first' => true]);
            /** if not found any template then remove */
            if (!isset($savedTemplate)) {
                $trainingLog->delete();
                return $this->sendSuccessResponse(null, __("validation.common.module_deleted", ["module" => $this->moduleName]));
            }
            $trainingLog->is_log = false;
            return $this->sendSuccessResponse(null, __("validation.common.module_deleted", ["module" => $this->moduleName]));
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->sendBadRequest(null, $exception->getMessage());
        }
    }

    /**
     * update => update training log and store save template
     *
     * @param mixed $id
     * @param mixed $request
     *
     * @return void
     */
    public function update($id, Request $request)
    {
        $input = $request->all();
        /** check first exist or not */
        $trainingLogCheck = $this->trainingLogRepository->getDetailsByInput([
            'id' => $id,
            'user_id' => $input['user_id'] ?? Auth::id(),
            'first' => true
        ]);
        if (!!!isset($trainingLogCheck)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }

        /** validation for training log details */
        $validator = TrainingLog::validation($input);
        if ($validator->fails()) {
            return $this->sendBadRequest(null, $validator->errors()->first());
        }

        /** check for activity validation */
        if ($input['status'] == TRAINING_LOG_STATUS_CARDIO && !isset($input['training_activity_id'])) {
            return $this->sendBadRequest(null, __("validation.common.select_any_value", ['module' => 'activity']));
        }

        /** convert date time to utc format */
        if (isset($input['date'])) {
            // $input['date'] = $this->utcToDateTimeFormat($input['date']);
            // $input['date'] = $this->isoToUTCFormat($input['date']);
        }

        /** create training log */
        $trainingLog = $this->trainingLogRepository->updateRich($input, $id);

        /** from app first set flag for save and then click on logit button with save template flag then after save workout template */
        /** when user save template then store log into saved_workout table */
        if (isset($input['is_saved_workout']) && $input['is_saved_workout'] == true) {
            $template = $this->saveWorkoutAsTemplate($trainingLog);
            if (isset($template) && $template['flag'] == false) {
                return $this->sendBadRequest(null, $template['message']);
            }
            // return $this->sendSuccessResponse( $template['data'], $template['message']);
        }

        /** give return data with relation */
        $returnDetailsRequest = [
            'id' => $trainingLog->id,
            'relation' => ["user_detail", "training_activity", "training_goal", "training_intensity"],
            'first' => true
        ];
        $trainingLog = $this->trainingLogRepository->getDetailsByInput($returnDetailsRequest);
        return $this->sendSuccessResponse($trainingLog, __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * saveTrainingLogReview => user can save their own feedback out of 10
     *      
     * @param mixed $id
     * @param mixed $request
     *   
     * @return void
     */
    public function saveTrainingLogReview($id, Request $request)
    {
        $input = $request->all();
        if (!isset($input['feedback'])) {
            return $this->sendBadRequest(null, __("validation.common.please_give_key", ["key" => 'feedback']));
        }
        /** check for feedback is greater then 10 */
        if ($input['feedback'] > 10 || $input['feedback'] < 0) {
            return $this->sendBadRequest(null, __("validation.min.numeric", ['min' => 10, "attribute" => "feedback"]));
        }
        /** update feedback */
        $updatedTrainingLog = $this->trainingLogRepository->updateRich(["user_own_review" => $input['feedback']], $id);
        return $this->sendSuccessResponse($updatedTrainingLog, __("validation.common.saved", ['module' => "feedback"]));
    }

    /**
     * completeTrainingLog -> to complete training log
     *
     * @param mixed $request
     * @param mixed $id
     *
     * @return void
     */
    public function completeTrainingLog(Request $request, $id)
    {
        $trainingLog = $this->trainingLogRepository->getDetailsByInput(['id' => $id, 'first' => true]);
        if (!isset($trainingLog)) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        $input = $request->all();

        // direct update data from input
        $trainingLog = $this->trainingLogRepository->update($input, $id);
        return $this->sendSuccessResponse($trainingLog, __('validation.common.saved', ['module' => $this->moduleName]));
    }

    public function listOfLogCardioValidations()
    {
        $logCardioValidations = $this->logCardioValidationsRepository->getDetailsByInput([
            'is_active' => true
        ]);
        if (isset($logCardioValidations) && count($logCardioValidations) == 0) return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Cardio validations"]));

        return $this->sendSuccessResponse($logCardioValidations, __('validation.common.details_found', ['module' => "Cardio validations"]));
    }

    public function saveGeneratedCalculations(Request $request)
    {
        $input = $request->input();
        $validation = $this->requiredValidation(['id'], $input);
        if (isset($validation) && $validation['flag'] == false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        # 1 Get Training Log Details
        $log = app(SummaryCalculationController::class)->getTrainingLogDetails($input['id']);
        if (!!!isset($log)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }

        /** check if generated calculation exists */
        if (isset($log->generated_calculations)) {

            /** if already updated then required generated_calculations */
            $validation = $this->requiredValidation(['generated_calculations'], $input);
            if (isset($validation) && $validation['flag'] == false) {
                return $this->sendBadRequest(null, $validation['message']);
            }

            $updateRequest = $log->generated_calculations;
            /** working code */
            $updateRequest['total_duration'] =
                $this->checkFromGeneratedCalculationByKey($log->generated_calculations, $input['generated_calculations'], 'total_duration');
            $updateRequest['total_distance'] =
                $this->checkFromGeneratedCalculationByKey($log->generated_calculations, $input['generated_calculations'], 'total_distance');
            $updateRequest['avg_pace'] =
                $this->checkFromGeneratedCalculationByKey($log->generated_calculations, $input['generated_calculations'], 'avg_pace');
            $updateRequest['avg_speed'] =
                $this->checkFromGeneratedCalculationByKey($log->generated_calculations, $input['generated_calculations'], 'avg_speed');
            $log->generated_calculations = $updateRequest;
            $log->save();
            return $this->sendSuccessResponse($log->generated_calculations, __('validation.common.updated', ['module' => $this->moduleName]));
        } else {
            $log = $log->toArray();
            $activityCode = $log['training_activity']['code'];
            $generated_calculations = [];

            /** generate calculation here at initial time */

            # Do calculates by Training Activity 
            # RUN ( IN | OUT ) // REVIEW PENDING
            if (in_array($activityCode, [TRAINING_ACTIVITY_CODE_RUN_INDOOR, TRAINING_ACTIVITY_CODE_RUN_OUTDOOR])) {
                // /** generate calculations from RunCalculationsController controller and return it. */
                // $response = app(RunCalculationsController::class)->generateRunCalculation($trainingLog);
                // $generated_calculations = array_merge($generated_calculations, $response);
            } elseif (in_array($activityCode, [TRAINING_ACTIVITY_CODE_CYCLE_INDOOR, TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR])) {
                /** get total_duration, total_distance, avg_speed, and avg_pace from CycleCalculationsController. */
                $response = $this->getGeneratedCalculationFromCycle($log, $activityCode);
                $generated_calculations = array_merge($generated_calculations, $response);
            }

            /** store calculated values */
            $log =  $this->trainingLogRepository->updateRich(['generated_calculations' => $generated_calculations], $input['id']);

            /** return first time generated */
            return $this->sendSuccessResponse($generated_calculations, __('validation.common.updated', ['module' => $this->moduleName]));
        }
    }

    public function checkFromGeneratedCalculationByKey($log_generated_calculations, $input_generated_calculations, $key)
    {
        // $log_generated_calculations = $log_generated_calculations->toArray();
        return isset($input_generated_calculations[$key])
            ? $input_generated_calculations[$key]
            : (isset($log_generated_calculations[$key])
                ? $log_generated_calculations[$key]
                : null);

        // return (isset($log_generated_calculations->$key, $input_generated_calculations[$key])
        //     && $log_generated_calculations->$key == $input_generated_calculations[$key])
        //     ? $log_generated_calculations->$key
        //     : ($input_generated_calculations[$key] ?? null);
    }

    public function getGeneratedCalculationFromCycle($trainingLog, $activityCode)
    {
        // MAIN
        $response = [];
        $isDuration = $trainingLog['exercise'][0]['duration'];

        # START Total Duration 
        $calculateDuration = app(CycleCalculationsController::class)->calculateDuration(
            $trainingLog,
            $isDuration
        );
        $response = array_merge($response, $calculateDuration);
        # END Total Duration 

        # START Total Distance 
        $calculateTotalDistance = app(CycleCalculationsController::class)->calculateTotalDistance(
            $trainingLog,
            $activityCode,
            $isDuration
        );
        $response = array_merge($response, $calculateTotalDistance);
        # END Total Distance 

        # START Average Speed (can be either km/hr OR mile/hr, depending on the unit setting) 
        $calculateAverageSpeed = app(CycleCalculationsController::class)->calculateAverageSpeed(
            $trainingLog,
            $response['total_distance'],
            $response['total_duration_minutes']
        );
        $response = array_merge($response, $calculateAverageSpeed);
        # END Average Speed (can be either km/hr OR mile/hr, depending on the unit setting) 

        return $response;
    }
}
