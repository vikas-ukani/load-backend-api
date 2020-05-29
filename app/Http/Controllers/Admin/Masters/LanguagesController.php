<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Models\Languages;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\LanguagesRepositoryEloquent;

class LanguagesController extends Controller
{
    protected $moduleName = "Language";
    protected $languagesRepository;

    public function __construct(LanguagesRepositoryEloquent $languagesRepository)
    {
        $this->languagesRepository = $languagesRepository;
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

        $languages = $this->languagesRepository->getDetails($input);
        if (isset($languages) && $languages['count'] == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($languages, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = Languages::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        $languages = $this->languagesRepository->create($input);
        return $this->sendSuccessResponse($languages->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $language = $this->languagesRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $language if null or not */
        if (!!!$language) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($language, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $language = $this->languagesRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $language if null or not */
        if (!!!$language) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = Languages::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $language = $this->languagesRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($language->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $language = $this->languagesRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$language if null or not */
        if (!!!$language) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->languagesRepository->delete($id);
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
            $this->languagesRepository->delete($id);
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

        $language = $this->languagesRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($language->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
