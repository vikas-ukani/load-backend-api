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
use App\Http\Controllers\API\v1\Calender\LogSummary\OtherCalculationController;
use App\Http\Controllers\API\v1\Calender\LogSummary\ResistanceCalculationController;
use App\Http\Controllers\API\v1\Calender\LogSummary\RunCalculationsController;
use App\Http\Controllers\API\v1\Calender\LogSummary\SummaryCalculationController;
use App\Http\Controllers\API\v1\Calender\LogSummary\SwimmingController;
use App\Libraries\Repositories\SettingTrainingRepositoryEloquent;
use App\Supports\SummaryCalculationTrait;

class TrainingLogController extends Controller
{
    use SummaryCalculationTrait;

    protected $moduleName = "Training Log";
    protected $savedTemplateModuleName = "Template";

    protected $trainingLogRepository;
    protected $savedWorkoutsRepository;
    protected $settingTrainingRepository;
    protected $trainingProgramsRepository;
    protected $trainingActivityRepository;
    protected $logCardioValidationsRepository;

    public function __construct(
        TrainingLogRepositoryEloquent $trainingLogRepository,
        SavedWorkoutsRepositoryEloquent $savedWorkoutsRepository,
        TrainingProgramsRepositoryEloquent $trainingProgramsRepository,
        TrainingActivityRepositoryEloquent $trainingActivityRepository,
        LogCardioValidationsRepositoryEloquent $logCardioValidationsRepository,
        SettingTrainingRepositoryEloquent $settingTrainingRepository
    ) {
        $this->trainingLogRepository = $trainingLogRepository;
        $this->savedWorkoutsRepository = $savedWorkoutsRepository;
        $this->settingTrainingRepository = $settingTrainingRepository;
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

    /**
     * show
     *
     * @param  mixed $id
     * @return void
     */
    public function show($id)
    {
        $trainingLog = $this->getLogDetailsById($id);
        if (!isset($trainingLog)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        $trainingLog = $trainingLog->toArray();

        $settingTraining = $this->settingTrainingRepository->getDetailsByInput([
            'user_id' => \Auth::id(),
            'list' => ['run_auto_pause', 'cycle_auto_pause'],
            'first' => true
        ]);
        // if (isset($settingTraining)) {
        $trainingLog['run_auto_pause'] = $settingTraining->run_auto_pause ?? true;
        $trainingLog['cycle_auto_pause'] = $settingTraining->cycle_auto_pause ?? true;
        // }

        return $this->sendSuccessResponse($trainingLog, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * getLogDetailsById
     *
     * @param  mixed $id
     * @return void
     */
    public function getLogDetailsById($id)
    {
        /** give return data with relation */
        $trainingLog = $this->trainingLogRepository->getDetailsByInput(
            [
                'id' => $id,
                'relation' => ["user_detail", "training_activity", "training_goal", "training_intensity", 'training_log_style'],
                'user_detail_list' => ['id', 'name', 'photo', 'country_id'],
                'training_activity_list' => ['id', 'name', 'icon_path', 'icon_path_red', 'is_active'],
                'training_goal_list' => ['id', 'name', 'is_active'],
                'training_intensity_list' => ['id', 'name', 'is_active'],
                'training_log_style_list' => ['id', 'name', 'is_active'],
                'first' => true
            ]
        );
        return $trainingLog;
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
        $trainingLog = $this->getLogDetailsById($trainingLog->id);

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

    /**
     * saveGeneratedCalculations => while update calculation from user
     *
     * @param  mixed $request
     * @return void
     */
    public function saveGeneratedCalculations(Request $request)
    {
        $input = $request->input();
        $validation = $this->requiredValidation(['id'], $input);
        if (isset($validation) && $validation['flag'] == false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        # 1 Get Training Log Details
        $log = app(SummaryCalculationController::class)->getTrainingLogDetails($input['id'], false);
        if (!!!isset($log)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        $log = $log->toArray();

        /** check if generated calculation exists */
        if (isset($log['generated_calculations'])) {
            /** if already updated then required generated_calculations */
            $validation = $this->requiredValidation(['generated_calculations'], $input);
            if (isset($validation) && $validation['flag'] == false) {
                return $this->sendBadRequest(null, $validation['message']);
            }

            $activityCode = $log['training_activity']['code'];
            $isDuration = $log['exercise'][0]['duration'];

            /** generate all new calculation */
            $generated_calculations = [];
            $response = $this->getGeneratedCalculationFromByActivity($log, $activityCode);
            $generated_calculations = array_merge($generated_calculations, $response);

            /** make a new request for replace old data */
            $storedDuration = $log['generated_calculations']['total_duration']; // TEST
            $storedDistance = $log['generated_calculations']['total_distance']; // TEST
            $log['generated_calculations'] = $generated_calculations;
            $updateRequest = $log['generated_calculations'];
            $log['generated_calculations']['total_duration'] = $storedDuration; // TEST
            $log['generated_calculations']['total_distance'] = round($storedDistance, 1); // TEST

            /** working code */
            $updateRequest['total_duration'] =
                $this->checkFromGeneratedCalculationByKey($log['generated_calculations'], $input['generated_calculations'], 'total_duration');
            $durationArr = explode(':', $updateRequest['total_duration']);
            $durationArr[0] = (int) $durationArr[0];
            $updateRequest['total_duration'] = implode(':', $durationArr);
            $updateRequest['total_distance'] =
                $this->checkFromGeneratedCalculationByKey($log['generated_calculations'], $input['generated_calculations'], 'total_distance');
            $updateRequest['total_distance'] = round($updateRequest['total_distance'], 1);

            /** convert 01:40 to 1:40 pace */
            $updateRequest['avg_pace'] =
                $this->checkFromGeneratedCalculationByKey($log['generated_calculations'], $input['generated_calculations'], 'avg_pace');
            $paceArr = explode(':', $updateRequest['avg_pace']);
            $paceArr[0] = (int) $paceArr[0];
            $updateRequest['avg_pace'] = implode(':', $paceArr);
            $updateRequest['avg_speed'] =
                $this->checkFromGeneratedCalculationByKey($log['generated_calculations'], $input['generated_calculations'], 'avg_speed');

            $ActivityCalculationController = $this->getActivityCalculationControllerNameByActivityCode($activityCode);
            if (isset($input['generated_calculations']['total_duration'])) {
                /** if user update in total_duration then calculate total_distance */
                $newSpeedAndPace = $this->generateNewSpeedAndPaceFromDurationAndDistanceViaActivityCodeName($log, $input['generated_calculations']['total_duration']); // OLD
                // NEW
                // $newSpeedAndPace = $this->generatePaceSpeedByTotalDistance_divide_by_TotalDuration(
                //     $log['generated_calculations']['total_distance'],
                //     $input['generated_calculations']['total_duration']
                // );
                /** set new calculated pace and speed value. */
                $updateRequest['total_duration_minutes'] = $newSpeedAndPace['total_duration_minutes']; // updated duration minutes
                $updateRequest['avg_pace'] = $newSpeedAndPace['avg_pace'];
                $paceArr = explode(':', $updateRequest['avg_pace']);
                $paceArr[0] = (int) $paceArr[0];
                $updateRequest['avg_pace'] = implode(':', $paceArr);
                $updateRequest['avg_speed'] = $newSpeedAndPace['avg_speed'];
            } else if (isset($input['generated_calculations']['total_distance'])) {
                /** if user update in total_distance then calculate avg_speed OR avg_pace */
                $newSpeedAndPace = $this->generateNewSpeedAndPaceFromDistanceAndDurationViaActivityCodeName($log, $input['generated_calculations']['total_distance']); // OLD
                // $newSpeedAndPace = $this->generatePaceSpeedByTotalDistance_divide_by_TotalDuration(
                //     $input['generated_calculations']['total_distance'],
                //     $log['generated_calculations']['total_duration'],
                //     $log['generated_calculations']['total_duration_minutes']
                // ); // NEW

                /** set new calculated pace and speed value. */
                $updateRequest['total_duration_minutes'] = $newSpeedAndPace['total_duration_minutes']; // updated duration minutes
                $updateRequest['avg_pace'] = $newSpeedAndPace['avg_pace'];
                $paceArr = explode(':', $updateRequest['avg_pace']);
                $paceArr[0] = (int) $paceArr[0];
                $updateRequest['avg_pace'] = implode(':', $paceArr);
                $updateRequest['avg_speed'] = $newSpeedAndPace['avg_speed'];
                // avg_speed OR avg_pace
            } else if (isset($input['generated_calculations']['avg_pace'])) {
                /** if user update in avg_speed then calculate total_duration */
                $newDuration = $this->generateNewDurationFromDistanceAndPaceViaActivityCodeName($log, $input['generated_calculations']['avg_pace']);
                /** set new calculated distance value. */
                $updateRequest['total_duration_minutes'] = $this->convertDurationToMinutes($newDuration);
                $updateRequest['total_duration'] = $newDuration;
                $durationArr = explode(':', $updateRequest['total_duration']);
                $durationArr[0] = (int) $durationArr[0];
                $updateRequest['total_duration'] = implode(':', $durationArr);
            } else if (isset($input['generated_calculations']['avg_speed'])) {
                /** if user update in avg_pace then calculate total_duration */
                $newDuration = $this->generateNewDurationFromDistanceAndSpeedViaActivityCodeName($log, $input['generated_calculations']['avg_speed']);
                $updateRequest['total_duration_minutes'] = $this->convertDurationToMinutes($newDuration);
                $updateRequest['total_duration'] = $newDuration;
                $durationArr = explode(':', $updateRequest['total_duration']);
                $durationArr[0] = (int) $durationArr[0];
                $updateRequest['total_duration'] = implode(':', $durationArr);
                // total_duration
            }

            $log['generated_calculations'] = $updateRequest;

            /** store calculated values */
            $logUpdated = $this->trainingLogRepository->updateRich(['generated_calculations' => $updateRequest], $log['id']);


            /** check for pase is selected or not */
            // $is_pace_selected = !!collect($log['exercise'])->whereIn('pace', [null])->pluck('pace')->first();
            $is_pace_selected = isset($log['exercise'][0], $log['exercise'][0]['pace']) ? true : false;
            // if ($is_pace_selected == false) {
            //     $is_pace_selected = !!collect($log['generated_calculations'])->pluck('pace')->first();
            // }

            $response = [
                'training_activity' => $log['training_activity'],
                'generated_calculations' => $updateRequest,
                'exercise' => $log['exercise'],
                'is_pace_selected' => $is_pace_selected ?? false
            ];


            return $this->sendSuccessResponse($response, __('validation.common.updated', ['module' => $this->moduleName]));
        } else {
            # Initial time
            if (!is_array($log))
                $log = $log->toArray();

            $activityCode = $log['training_activity']['code'];
            $generated_calculations = [];

            /** get total_duration, total_distance, avg_speed, and avg_pace from CycleCalculationsController. */

            $response = $this->getGeneratedCalculationFromByActivity($log, $activityCode);
            $generated_calculations = array_merge($generated_calculations, $response);

            /** store calculated values */
            $logUpdated = $this->trainingLogRepository->updateRich(['generated_calculations' => $generated_calculations], $input['id']);
            /** return first time generated */

            /** check for pase is selected or not */
            // $is_pace_selected = !!collect($log['exercise'])->pluck('pace')->first();
            $is_pace_selected = isset($log['exercise'][0], $log['exercise'][0]['pace']) ? true : false;
            // if ($is_pace_selected == false) {
            //     $is_pace_selected = !!collect($log['generated_calculations'])->pluck('pace')->first();
            // }

            $response = [
                'training_activity' => $log['training_activity'],
                'generated_calculations' => $logUpdated->generated_calculations,
                'exercise' => $logUpdated->exercise,
                'is_pace_selected' => $is_pace_selected ?? false
            ];
            return $this->sendSuccessResponse($response, __('validation.common.updated', ['module' => $this->moduleName]));
        }
    }

    /**
     * checkFromGeneratedCalculationByKey
     *
     * @param  mixed $log_generated_calculations
     * @param  mixed $input_generated_calculations
     * @param  mixed $key
     * @return void
     */
    public function checkFromGeneratedCalculationByKey($log_generated_calculations, $input_generated_calculations, $key)
    {
        /** set value from input if not in input then from get log */
        return isset($input_generated_calculations[$key])
            ? $input_generated_calculations[$key]
            : (isset($log_generated_calculations[$key])
                ? $log_generated_calculations[$key]
                : null);
    }

    /**
     * getGeneratedCalculationFromByActivity
     *
     * @param  mixed $trainingLog
     * @param  mixed $activityCode
     * @return void
     */
    public function getGeneratedCalculationFromByActivity($trainingLog, $activityCode)
    {
        // MAIN
        $response = [];
        $isDuration = $trainingLog['exercise'][0]['duration'];

        $ActivityCalculationController = $this->getActivityCalculationControllerNameByActivityCode($activityCode);

        # START Total Duration 
        $calculateDuration = $ActivityCalculationController->calculateDuration(
            $trainingLog,
            $isDuration
        );
        $response = array_merge($response, $calculateDuration);
        # END Total Duration

        # START Total Distance 
        $calculateTotalDistance = $ActivityCalculationController->calculateTotalDistance(
            $trainingLog,
            $activityCode,
            $isDuration
        );
        $response = array_merge($response, $calculateTotalDistance);
        # END Total Distance 

        # START Average Speed (can be either km/hr OR mile/hr, depending on the unit setting) 
        if (method_exists($ActivityCalculationController, 'calculateAverageSpeed')) {
            $calculateAverageSpeed = $ActivityCalculationController->calculateAverageSpeed(
                $trainingLog['exercise'],
                $activityCode,
                $response['total_distance'],
                $response['total_duration_minutes']
            );
        } else {
            $calculateAverageSpeed = [
                'avg_speed' => null,
                'avg_speed_unit' => null,
                'avg_speed_code' => null
            ];
        }
        $response = array_merge($response, $calculateAverageSpeed);
        # END Average Speed (can be either km/hr OR mile/hr, depending on the unit setting) 

        # 3. START Average Pace
        /** check for method exists in thi controller */
        if (method_exists($ActivityCalculationController, 'calculateAvgPace')) {
            $calculateAvgPace = $ActivityCalculationController->calculateAvgPace(
                $trainingLog['exercise'],
                $response['total_distance'],
                $response['total_duration_minutes'],
                $activityCode
            );
        } else {
            $calculateAvgPace = [
                'avg_pace'      =>  null,
                'avg_pace_unit' =>  null,
                'avg_pace_code' =>  null
            ];
        }
        $response = array_merge($response, $calculateAvgPace);
        # 3. END Average Pace 

        return $response;
    }

    /**
     * getActivityCalculationControllerNameByActivityCode
     *
     * @param  mixed $activityCode
     * @return object
     */
    public function getActivityCalculationControllerNameByActivityCode($activityCode)
    {
        if (in_array($activityCode, [TRAINING_ACTIVITY_CODE_RUN_INDOOR, TRAINING_ACTIVITY_CODE_RUN_OUTDOOR])) {
            return app(RunCalculationsController::class);
        } elseif (in_array($activityCode, [TRAINING_ACTIVITY_CODE_CYCLE_INDOOR, TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR])) {
            return app(CycleCalculationsController::class);
        } elseif (in_array($activityCode, [TRAINING_ACTIVITY_CODE_SWIMMING])) {
            return app(SwimmingController::class);
        } else if (in_array($activityCode, [TRAINING_ACTIVITY_CODE_OTHERS])) {
            return app(OtherCalculationController::class);
        } else {
            return app(ResistanceCalculationController::class);
        }
    }

    /**
     * generateNewSpeedAndPaceFromDurationAndDistanceViaActivityCodeName
     * => Calculate New Distance  From New Duration.
     * @param  mixed $trainingLog
     * @param  mixed $newInputtedTotalDuration
     * @return void
     */
    public function generateNewSpeedAndPaceFromDurationAndDistanceViaActivityCodeName($trainingLog, $newInputtedTotalDuration)
    {
        $newDistance = $trainingLog['generated_calculations']['total_distance'] ?? 0;

        $durationMinutes = $this->convertDurationToMinutes($newInputtedTotalDuration);

        // if (in_array($activityCode, [TRAINING_ACTIVITY_CODE_RUN_INDOOR, TRAINING_ACTIVITY_CODE_RUN_OUTDOOR])) {
        /** Run activity */
        # Pace = Time / Distance        
        $avgPace = ($newDistance == 0 ? 0 : ($durationMinutes / $newDistance));
        $avgPace = round($avgPace, 4);

        # Speed = 60 / Pace
        $avgSpeed = ($avgPace == 0 ? 0 : round((60 / $avgPace), 1));

        $newAvgPace = $this->convertPaceNumberTo_M_S_format($avgPace);
        // dd(
        //     'Calculation Pace From Duration Here',
        //     "durationMinutes " . $durationMinutes,
        //     "newDistance " . $newDistance,
        //     " ($durationMinutes / $newDistance) => " .  $avgPace,
        //     "avgPace " . $avgPace,
        //     "newAvgPace " . $newAvgPace,
        //     "avgSpeed " . $avgSpeed
        // );
        return [
            'total_duration_minutes' => $durationMinutes,
            'avg_pace' => $newAvgPace,
            'avg_speed' => $avgSpeed
        ];
    }

    /**
     * generateNewSpeedAndPaceFromDistanceAndDurationViaActivityCodeName => Calculate New Speed | Pace
     *
     * @param  mixed $trainingLog
     * @param  mixed $newInputtedDistance
     * @return void
     */
    public function generateNewSpeedAndPaceFromDistanceAndDurationViaActivityCodeName($trainingLog, $newInputtedDistance)
    {
        $avgSpeed = $trainingLog['generated_calculations']['avg_speed'] ?? 0;
        $avgPace = $trainingLog['generated_calculations']['avg_pace'] ?? 0;
        $activityCode = $trainingLog['training_activity']['code'];

        $durationMinutes = $this->convertDurationToMinutes($trainingLog['generated_calculations']['total_duration']);

        /** Run activity */
        # Pace = Time / Distance

        $avgPace = $newInputtedDistance == 0 ? 0 : ($durationMinutes / $newInputtedDistance);

        # Speed = 60 / Pace
        $avgSpeed = $avgPace == 0 ? 0 : (60 / $avgPace);

        # Speed = distance / duration (minute)
        // $avgSpeed = 60 / $avgPace;

        $newAvgPace = $this->convertPaceNumberTo_M_S_format($avgPace);

        return [
            'avg_pace' => $newAvgPace,
            'avg_speed' => round($avgSpeed, 1),
            'total_duration_minutes' => $durationMinutes
        ];
    }

    /**
     * generateNewDurationFromDistanceAndPaceViaActivityCodeName => Calculate New Duration Using new Pace
     *
     * @param  mixed $trainingLog
     * @param  mixed $newInputtedPace
     * @return void
     */
    public function generateNewDurationFromDistanceAndPaceViaActivityCodeName($trainingLog, $newInputtedPace)
    {
        $newDurationMinutes = 0;

        $avgPace = $newInputtedPace;
        $activityCode = $trainingLog['training_activity']['code'];
        $distance = $trainingLog['generated_calculations']['total_distance'];
        $distance = round($distance, 1);

        $paceToSpeedArray = explode(':', $avgPace);
        $paceToMinutes = ($paceToSpeedArray[0]) + ($paceToSpeedArray[1] / 60);
        $newSpeed = round((60 / $paceToMinutes), 4);
        $newDurationMinutes = round((($distance / $newSpeed)  * 60), 2);

        return $this->convertDurationMinutesToTimeFormat($newDurationMinutes);
        // return (gmdate("H:i:s", (($newDurationMinutes ?? 0)  * 60)));
    }

    /**
     * generateNewDurationFromDistanceAndSpeedViaActivityCodeName => Calculate New Duration Using new Speed
     *
     * @param  mixed $trainingLog
     * @param  mixed $newInputtedSpeed
     * @return void
     */
    public function generateNewDurationFromDistanceAndSpeedViaActivityCodeName($trainingLog, $newInputtedSpeed)
    {
        $activityCode = $trainingLog['training_activity']['code'];
        $distance = $trainingLog['generated_calculations']['total_distance'];

        // if (in_array($activityCode, [TRAINING_ACTIVITY_CODE_RUN_INDOOR, TRAINING_ACTIVITY_CODE_RUN_OUTDOOR])) {
        // Duration = Distance / Speed
        $newDurationMinutes = ($distance / $newInputtedSpeed) * 60;
        // $newDuration = (gmdate("H:i:s", (($duration ?? 0)  * 60)));
        // }
        return (gmdate("H:i:s", (($newDurationMinutes ?? 0)  * 60)));
    }

    /**
     * generatePaceSpeedByTotalDistance_divide_by_TotalDuration
     *
     * @param  mixed $TotalDistance
     * @param  mixed $TotalDuration
     * @param  mixed $TotalDurationMinutes
     * @return void
     */
    public function generatePaceSpeedByTotalDistance_divide_by_TotalDuration($TotalDistance, $TotalDuration, $TotalDurationMinutes = 0)
    {
        if ($TotalDurationMinutes == 0) {
            $TotalDurationMinutes = $this->convertDurationToMinutes($TotalDuration);
        }

        # Formula for Speed and Pace
        # Average Speed (km/hr) = Total Distance / Total Duration
        # NOTE: We can find Average Pace by converting Average Speed. Use this equation:
        # Average Pace (mins/km) = 60 / Average Speed
        # • Requires conversion of Average Pace fraction to time

        $avg_speed = $TotalDistance / $TotalDurationMinutes;
        $avgPace = 60 / $avg_speed;
        $avg_pace = $this->convertPaceNumberTo_M_S_format($avgPace); # • Requires conversion of Average Pace fraction to time
        // dd('Updated', $TotalDistance, $TotalDuration, $TotalDurationMinutes, "New Generated", $avgPace, $avg_pace, $avg_speed, $TotalDurationMinutes);
        return [
            'avg_pace' => $avg_pace,
            'avg_speed' => $avg_speed,
            'total_duration_minutes' => $TotalDurationMinutes
        ];
    }
}
