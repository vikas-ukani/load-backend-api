<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Models\Currency;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\CurrencyRepositoryEloquent;

class CurrencyController extends Controller
{
    protected $moduleName = "Currency";
    protected $currencyRepository;

    /**
     * __construct => Repos injections
     *
     * @param  mixed $currencyRepository
     *
     * @return void
     */
    public function __construct(CurrencyRepositoryEloquent $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
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
        $currencies = $this->currencyRepository->getDetails($input);
        if (isset($currencies) && $currencies['count'] === 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($currencies, __('validation.common.details_found', ['module' => $this->moduleName]));
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
        $validation = Currency::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $currencies = $this->currencyRepository->create($input);
        return $this->sendSuccessResponse($currencies->fresh(), __("validation.common.created", ['module' => $this->moduleName]));
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
        $currency = $this->currencyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $currency if null or not */
        if (!!!$currency) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($currency, __('validation.common.details_found', ['module' => $this->moduleName]));
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

        $currency = $this->currencyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load $currency if null or not */
        if (!!!$currency) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** check model validation */
        $validation = Currency::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }
        $currency = $this->currencyRepository->updateRich($input, $id);
        return $this->sendSuccessResponse($currency->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $currency = $this->currencyRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );
        /** check load$currency if null or not */
        if (!!!$currency) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $this->currencyRepository->delete($id);
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
            $this->currencyRepository->delete($id);
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

        $currency = $this->currencyRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($currency->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
