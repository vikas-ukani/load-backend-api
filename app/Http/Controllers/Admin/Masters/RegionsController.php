<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Models\Region;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\RegionRepositoryEloquent;

class RegionsController extends Controller
{
    protected $moduleName = "Region";
    protected $regionRepository;

    public function __construct(RegionRepositoryEloquent $regionRepository)
    {
        $this->regionRepository = $regionRepository;
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

        $region = $this->regionRepository->getDetails($input);
        if (isset($region) && $region['count'] == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($region, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = Region::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        $region = $this->regionRepository->create($input);
        return $this->sendSuccessResponse($region->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $region = $this->regionRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $region if null or not */
        if (!!!$region) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($region, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $region = $this->regionRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );

        /** check load $region if null or not */
        if (!!!$region) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = Region::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $region = $this->regionRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($region->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $region = $this->regionRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$region if null or not */
        if (!!!$region) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->regionRepository->delete($id);
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
            $this->regionRepository->delete($id);
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

        $region = $this->regionRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($region->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
