<?php

namespace App\Http\Controllers\Admin\Masters;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TargetedMuscles;
use App\Libraries\Repositories\TrainingFrequencyRepositoryEloquent;
use App\Models\TrainingFrequencies;

class TargetedFrequenciesController extends Controller
{
    protected $moduleName = "Targeted frequency";
    protected $trainingFrequencyRepository;

    public function __construct(TrainingFrequencyRepositoryEloquent $trainingFrequencyRepository)
    {
        $this->trainingFrequencyRepository = $trainingFrequencyRepository;
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

        $trainingFrequency = $this->trainingFrequencyRepository->getDetails($input);
        if (isset($trainingFrequency) && $trainingFrequency['count'] == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($trainingFrequency, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = TrainingFrequencies::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        $trainingFrequency = $this->trainingFrequencyRepository->create($input);
        return $this->sendSuccessResponse($trainingFrequency->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $trainingFrequency = $this->trainingFrequencyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $trainingFrequency if null or not */
        if (!!!$trainingFrequency) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($trainingFrequency, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $trainingFrequency = $this->trainingFrequencyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );

        /** check load $trainingFrequency if null or not */
        if (!!!$trainingFrequency) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = TrainingFrequencies::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $trainingFrequency = $this->trainingFrequencyRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($trainingFrequency->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $trainingFrequency = $this->trainingFrequencyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check $loadTargetedMuscles if null or not */
        if (!!!$trainingFrequency) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->trainingFrequencyRepository->delete($id);
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
            $this->trainingFrequencyRepository->delete($id);
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

        $trainingFrequency = $this->trainingFrequencyRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($trainingFrequency->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
