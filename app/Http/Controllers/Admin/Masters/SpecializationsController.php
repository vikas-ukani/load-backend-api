<?php

namespace App\Http\Controllers\Admin\Masters;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\SpecializationsRepositoryEloquent;
use App\Models\Specializations;

class SpecializationsController extends Controller
{
    protected $moduleName = "Specialization";
    protected $specializationsRepository;

    public function __construct(SpecializationsRepositoryEloquent $specializationsRepository)
    {
        $this->specializationsRepository = $specializationsRepository;
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

        $specializations = $this->specializationsRepository->getDetails($input);
        if (isset($specializations) && $specializations['count'] == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($specializations, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = Specializations::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        /** create specialization */
        $specializations = $this->specializationsRepository->create($input);
        return $this->sendSuccessResponse($specializations->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $specialization = $this->specializationsRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $specialization if null or not */
        if (!!!$specialization) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($specialization, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $specialization = $this->specializationsRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );

        /** check load $specialization if null or not */
        if (!!!$specialization) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = Specializations::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $specialization = $this->specializationsRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($specialization->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $specialization = $this->specializationsRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$specialization if null or not */
        if (!!!$specialization) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->specializationsRepository->delete($id);
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
            $this->specializationsRepository->delete($id);
        }
        return $this->sendSuccessResponse(null, __('validation.common.deleted'));
    }

    /**
     * statusChange =>
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function statusChange(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['id'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        $specialization = $this->specializationsRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($specialization->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
