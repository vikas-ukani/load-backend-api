<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Models\RepetitionMax;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\RepetitionMaxRepositoryEloquent;

class RepetitionMaxController extends Controller
{
    protected $moduleName = "Repetition max";
    protected $repetitionMaxRepository;

    public function __construct(RepetitionMaxRepositoryEloquent $repetitionMaxRepository)
    {
        $this->repetitionMaxRepository = $repetitionMaxRepository;
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

        $repetitionMax = $this->repetitionMaxRepository->getDetails($input);
        if (isset($repetitionMax) && $repetitionMax['count'] == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($repetitionMax, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = RepetitionMax::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        $repetitionMax = $this->repetitionMaxRepository->create($input);
        return $this->sendSuccessResponse($repetitionMax->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $repetitionMax = $this->repetitionMaxRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $repetitionMax if null or not */
        if (!!!$repetitionMax) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($repetitionMax, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $repetitionMax = $this->repetitionMaxRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );

        /** check load $repetitionMax if null or not */
        if (!!!$repetitionMax) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = RepetitionMax::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $repetitionMax = $this->repetitionMaxRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($repetitionMax->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $repetitionMax = $this->repetitionMaxRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$repetitionMax if null or not */
        if (!!!$repetitionMax) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->repetitionMaxRepository->delete($id);
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
            $this->repetitionMaxRepository->delete($id);
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

        $repetitionMax = $this->repetitionMaxRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($repetitionMax->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
