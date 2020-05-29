<?php

namespace App\Http\Controllers\Admin\Masters;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\CommonProgramsWeekRepositoryEloquent;
use App\Libraries\Repositories\TrainingActivityRepositoryEloquent;
use App\Libraries\Repositories\TrainingGoalRepositoryEloquent;
use App\Libraries\Repositories\TrainingIntensityRepositoryEloquent;
use App\Models\CommonProgramsWeek;

class PresetProgramsWeeksController extends Controller
{
    protected $moduleName = "Preset Program Week";
    protected $trainingGoalRepository;
    protected $trainingIntensityRepository;
    protected $trainingActivityRepository;
    protected $commonProgramsWeekRepository;

    public function __construct(
        TrainingGoalRepositoryEloquent $trainingGoalRepository,
        TrainingActivityRepositoryEloquent $trainingActivityRepository,
        TrainingIntensityRepositoryEloquent $trainingIntensityRepository,
        CommonProgramsWeekRepositoryEloquent $commonProgramsWeekRepository
    ) {
        $this->trainingGoalRepository = $trainingGoalRepository;
        $this->trainingActivityRepository = $trainingActivityRepository;
        $this->trainingIntensityRepository = $trainingIntensityRepository;
        $this->commonProgramsWeekRepository = $commonProgramsWeekRepository;
    }

    /**
     * list =>  Get All user listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function list(Request $request)
    {
        $input = $request->all();

        $trainingType = $this->commonProgramsWeekRepository->getDetails($input);
        if (isset($trainingType) && $trainingType['count'] == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($trainingType, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        /** check model validation */
        $validation = CommonProgramsWeek::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        $trainingType = $this->commonProgramsWeekRepository->create($input);
        $trainingType = $trainingType->fresh();

        $trainingType = $this->commonProgramsWeekRepository->getDetailsByInput([
            'id' => $trainingType->id,
            'relation' => [
                "training_activity_detail", "training_goal_detail", "training_intensity_detail"
            ],
            'first' => true
        ]);
        return $this->sendSuccessResponse($trainingType, __("validation.common.created", ['module' => $this->moduleName]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        /** get detail by id */
        $trainingType = $this->commonProgramsWeekRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $trainingType if null or not */
        if (!!!$trainingType) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($trainingType, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();

        $trainingType = $this->commonProgramsWeekRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );

        /** check load $trainingType if null or not */
        if (!!!$trainingType) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = CommonProgramsWeek::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $trainingType = $this->commonProgramsWeekRepository->updateRich($input, $id);
        $trainingType = $this->commonProgramsWeekRepository->getDetailsByInput([
            'id' => $trainingType->id,
            'relation' => [
                "training_activity_detail", "training_goal_detail", "training_intensity_detail"
            ],
            'first' => true
        ]);
        return $this->sendSuccessResponse($trainingType, __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $trainingType = $this->commonProgramsWeekRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$trainingType if null or not */
        if (!!!$trainingType) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->commonProgramsWeekRepository->delete($id);
        return $this->sendSuccessResponse(null, __("validation.common.deleted"));
    }

    /**
     * DeleteMany => to delete multiple records
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function DeleteMany(Request $request)
    {
        $input =  $request->all();
        /** check validation for ids */
        $validation = $this->requiredValidation(['ids'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }
        foreach ($input['ids'] as  $id) {
            $this->commonProgramsWeekRepository->delete($id);
        }
        return $this->sendSuccessResponse(null, __('validation.common.deleted'));
    }

    public function statusChange(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['id'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        $trainingType = $this->commonProgramsWeekRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($trainingType->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * UpdateSequenceMany => update sequences for all records
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function UpdateSequenceMany(Request $request)
    {
        $input = $request->all();

        foreach ($input['sequences'] as $list) {

            $this->commonProgramsWeekRepository->updateRich(['sequence' => $list['sequence']], $list['id']);
        }
        return $this->sendSuccessResponse(null, __('validation.common.updated', ['module' => $this->moduleName]));
    }
}
