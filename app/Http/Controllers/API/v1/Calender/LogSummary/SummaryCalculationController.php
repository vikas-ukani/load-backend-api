<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\Calender\LogSummary;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\TrainingLogRepositoryEloquent;

class SummaryCalculationController extends Controller
{
    protected $trainingLogRepository;

    public function __construct(
        TrainingLogRepositoryEloquent $trainingLogRepository
    ) {
        $this->trainingLogRepository = $trainingLogRepository;
    }

    /**
     * generateSummaryDetails => Generate Training Log Summary Details
     *
     * @param  mixed $request
     * @return void
     */
    public function generateSummaryDetails(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['id'/* , 'status' */], $input);
        if (isset($validation) && $validation['flag'] == false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        # 1 Get Training Log Details
        $trainingLog = $this->getTrainingLogDetails($input['id']);
        if (!!!isset($trainingLog)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Training Details"]));
        }
        $trainingLog = $trainingLog->toArray();
        $activityCode = $trainingLog['training_activity']['code'];

        # 2 Get all Information
        $summaryResponse['id'] = $trainingLog['id'];
        $summaryResponse['training_activity'] = $trainingLog['training_activity'] ?? null;
        $summaryResponse['training_goal'] = $trainingLog['training_goal'] ?? null;
        $summaryResponse['training_goal_custom'] = $trainingLog['training_goal_custom'] ?? null;
        $summaryResponse['training_intensity'] = $trainingLog['training_intensity'] ?? null;
        $summaryResponse['training_log_style'] = $trainingLog['training_log_style'] ?? null;
        $summaryResponse['workout_name'] = $trainingLog['workout_name'] ?? null;
        $summaryResponse['notes'] = $trainingLog['notes'] ?? null;
        $summaryResponse['comments'] = $trainingLog['comments'] ?? null;
        $summaryResponse['exercise'] = $trainingLog['exercise'] ?? null;
        $summaryResponse['RPE'] = $trainingLog['RPE'] ?? null;

        $summaryResponse['date'] = $trainingLog['date'];
        $summaryResponse['targeted_hr'] = $trainingLog['targeted_hr'] ?? null;

        # 3 Apply Summary Calculations activity wise ( activity wise different calculations )
        // dd('asd', $activityCode);
        if (in_array($activityCode, [TRAINING_ACTIVITY_CODE_RUN_INDOOR, TRAINING_ACTIVITY_CODE_RUN_OUTDOOR])) {
            /** generate calculations from RunCalculationsController controller and return it. */
            $response = app(RunCalculationsController::class)->generateRunCalculation($trainingLog);
            $summaryResponse = array_merge($summaryResponse, $response);
        } elseif (in_array($activityCode, [TRAINING_ACTIVITY_CODE_CYCLE_INDOOR, TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR])) {
            /** generate calculations from RunCalculationsController controller and return it. */
            $response = app(CycleCalculationsController::class)->generateCalculation($trainingLog, $activityCode);
            $summaryResponse = array_merge($summaryResponse, $response);
        } elseif (in_array($activityCode, [TRAINING_ACTIVITY_CODE_SWIMMING])) {
            $response = app(SwimmingController::class)->generateCalculation($trainingLog, $activityCode);
            $summaryResponse = array_merge($summaryResponse, $response);
        } else if (in_array($activityCode, [TRAINING_ACTIVITY_CODE_OTHERS])) {
            $response = app(OtherCalculationController::class)->generateCalculation($trainingLog, $activityCode);
            $summaryResponse = array_merge($summaryResponse, $response);
        } else {
            /** Else Means no Activity ( RESiSTANCE TRAINING LOG )*/
            $response = app(ResistanceCalculationController::class)->generateCalculation($trainingLog, $activityCode);
            $summaryResponse = array_merge($summaryResponse, $response);
        }

        // dd('check', $summaryResponse, $trainingLog);
        # 4 return all details.
        return $this->sendSuccessResponse($summaryResponse, __('validation.common.details_found', ['module' => "Summary"]));
    }

    public function getTrainingLogDetails($id)
    {
        $trainingLog = $this->trainingLogRepository->getDetailsByInput([
            'id' => $id,
            'relation' => [
                'training_activity',
                'training_goal',
                'training_intensity',
                'training_log_style',
                'user_detail',
            ],
            'training_activity_list' => ['id', "name", 'code'],
            'training_goal_list' => ['id', "name", 'code', 'target_hr'],
            'training_intensity_list' => ['id', "name", 'code', 'target_hr'],
            'training_log_style_list' => ['id', "name", 'code', 'mets'],
            'user_detail_list' => ['id', "name", 'weight', 'height'],
            "is_complete" => true,
            'first' => true
        ]);
        return $trainingLog;
    }
}
