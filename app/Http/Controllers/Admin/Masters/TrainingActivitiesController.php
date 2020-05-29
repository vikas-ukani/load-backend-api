<?php

namespace App\Http\Controllers\Admin\Masters;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\TrainingActivityRepositoryEloquent;
use App\Models\TrainingActivity;
use App\Http\Controllers\ImageHelperController;

class TrainingActivitiesController extends Controller
{
    protected $moduleName = "Training activity";
    protected $imageController;
    protected $trainingActivityRepository;

    public function __construct(TrainingActivityRepositoryEloquent $trainingActivityRepository, ImageHelperController $imageController)
    {
        $this->trainingActivityRepository = $trainingActivityRepository;
        $this->imageController = $imageController;
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

        $trainingActivities = $this->trainingActivityRepository->getDetails($input);
        if (isset($trainingActivities) && $trainingActivities['count'] == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($trainingActivities, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = TrainingActivity::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        // upload image at create time and update time
        if (isset($input['icon_path'])) {
            $data =  $this->imageController->moveFile($input['icon_path'], 'training_activity');
            if (isset($data) && $data['flag'] == false) {
                return $this->makeError(null, $data['message']);
            }
            $input['icon_path'] = $data['data']['image'];
        }


        $trainingActivities = $this->trainingActivityRepository->create($input);
        return $this->sendSuccessResponse($trainingActivities->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $trainingActivities = $this->trainingActivityRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $trainingActivities if null or not */
        if (!!!$trainingActivities) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($trainingActivities, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $trainingActivities = $this->trainingActivityRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $trainingActivities if null or not */
        if (!!!$trainingActivities) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        /** check model validation */
        $validation = TrainingActivity::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        // upload image at create time and update time
        if (isset($input['icon_path'])) {
            $data =  $this->imageController->moveFile($input['icon_path'], 'training_activity');
            if (isset($data) && $data['flag'] == false) {
                return $this->makeError(null, $data['message']);
            }
            $input['icon_path'] = $data['data']['image'];
        }

        $newTrainingActivities = $this->trainingActivityRepository->updateRich($input, $id);

        /** check for photo is exist then remove  old image */
        if (isset($input['icon_path'])) {
            /** remove old image whe data was updated */
            $this->imageController->removeImageFromStorage($trainingActivities->icon_path); // use old image path here
        }
        return $this->sendSuccessResponse($newTrainingActivities->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $trainingActivities = $this->trainingActivityRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check $loadtargetedMuscles if null or not */
        if (!!!$trainingActivities) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->trainingActivityRepository->delete($id);
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
            $this->trainingActivityRepository->delete($id);
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

        $trainingActivities = $this->trainingActivityRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($trainingActivities->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
