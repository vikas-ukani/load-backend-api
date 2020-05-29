<?php

namespace App\Http\Controllers\Admin\Masters;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\BodyPartRepositoryEloquent;
use App\Models\BodyParts;

class BodyPartController extends Controller
{
    protected $moduleName = "Body part";
    protected $bodyPartRepository;

    public function __construct(BodyPartRepositoryEloquent $bodyPartRepository)
    {
        $this->bodyPartRepository = $bodyPartRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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

        $bodyParts = $this->bodyPartRepository->getDetails($input);

        if (isset($bodyParts) && $bodyParts['count'] === 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($bodyParts, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = BodyParts::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        /** set parent id is  null when is Parent is false */
        if (isset($input['is_parent']) && $input['is_parent'] === false) {
            $input['parent_id'] = null;
        }

        $bodyParts = $this->bodyPartRepository->create($input);
        return $this->sendSuccessResponse($bodyParts->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $bodyPart = $this->bodyPartRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$bodyPart if null or not */
        if (!!!$bodyPart) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($bodyPart, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $bodyPart = $this->bodyPartRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$bodyPart if null or not */
        if (!!!$bodyPart) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = BodyParts::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        /** set parent id is  null when is Parent is false */
        if (isset($input['is_parent']) && $input['is_parent'] === false) {
            $input['parent_id'] = null;
        }

        $bodyPart = $this->bodyPartRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($bodyPart->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $bodyPart = $this->bodyPartRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$bodyPart if null or not */
        if (!!!$bodyPart) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->bodyPartRepository->delete($id);
        return $this->sendSuccessResponse(null, __("validation.common.deleted"));
    }

    public function statusChange(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['id'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        $bodyPart = $this->bodyPartRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($bodyPart->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
