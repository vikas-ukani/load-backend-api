<?php

namespace App\Http\Controllers\Admin\Masters;

use Illuminate\Http\Request;
use App\Models\CancellationPolicy;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\CancellationPolicyRepositoryEloquent;

class CancellationPolicyController extends Controller
{
    protected $moduleName = "Cancellation policy";
    protected $cancellationPolicyRepository;

    /**
     * __construct => Repos injections
     *
     * @param  mixed $cancellationPolicyRepository
     *
     * @return void
     */
    public function __construct(CancellationPolicyRepositoryEloquent $cancellationPolicyRepository)
    {
        $this->cancellationPolicyRepository = $cancellationPolicyRepository;
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
        $cancellationPolicy = $this->cancellationPolicyRepository->getDetails($input);
        if (isset($cancellationPolicy) && $cancellationPolicy['count'] === 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($cancellationPolicy, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = CancellationPolicy::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $cancellationPolicy = $this->cancellationPolicyRepository->create($input);
        return $this->sendSuccessResponse($cancellationPolicy->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $cancellationPolicy = $this->cancellationPolicyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $cancellationPolicy if null or not */
        if (!!!$cancellationPolicy) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($cancellationPolicy, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $cancellationPolicy = $this->cancellationPolicyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $cancellationPolicy if null or not */
        if (!!!$cancellationPolicy) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = CancellationPolicy::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $cancellationPolicy = $this->cancellationPolicyRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($cancellationPolicy->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $cancellationPolicy = $this->cancellationPolicyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$cancellationPolicy if null or not */
        if (!!!$cancellationPolicy) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->cancellationPolicyRepository->delete($id);
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
            $this->cancellationPolicyRepository->delete($id);
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

        $cancellationPolicy = $this->cancellationPolicyRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($cancellationPolicy->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
