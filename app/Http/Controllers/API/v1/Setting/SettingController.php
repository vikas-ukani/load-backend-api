<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\Setting;

use App\Http\Controllers\API\v1\PaypalController;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\BillingInformationRepositoryEloquent;
use App\Libraries\Repositories\LanguagesRepositoryEloquent;
use App\Libraries\Repositories\SettingPremiumRepositoryEloquent;
use App\Libraries\Repositories\SettingTrainingRepositoryEloquent;
use App\Libraries\Repositories\SpecializationsRepositoryEloquent;
use App\Libraries\Repositories\TrainingSettingUnitsRepositoryEloquent;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Models\BillingInformation;
use App\Supports\DateConvertor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// use App\Models\SettingTraining;

class SettingController extends Controller
{
    use DateConvertor;


    protected $usersRepository;
    protected $languagesRepository;
    protected $settingPremiumRepository;
    protected $settingTrainingRepository;
    protected $specializationsRepository;
    protected $billingInformationRepository;

    public function __construct(
        UsersRepositoryEloquent $usersRepository,
        LanguagesRepositoryEloquent $languagesRepository,
        SettingPremiumRepositoryEloquent $settingPremiumRepository,
        SettingTrainingRepositoryEloquent $settingTrainingRepository,
        BillingInformationRepositoryEloquent $billingInformationRepository,
        SpecializationsRepositoryEloquent $specializationsRepository
    ) {
        $this->usersRepository = $usersRepository;
        $this->languagesRepository = $languagesRepository;
        $this->settingPremiumRepository = $settingPremiumRepository;
        $this->specializationsRepository = $specializationsRepository;
        $this->settingTrainingRepository = $settingTrainingRepository;
        $this->billingInformationRepository = $billingInformationRepository;
    }


    /** Create and update setting programs
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUpdateSettingProgram(Request $request)
    {
        $input = $request->all();
        $userId = $input['user_id'] ?? Auth::id();

        if (isset($input['height'], $input['weight'])) {
            $this->usersRepository->updateRich([
                'height' => $input['height'],
                'weight' => $input['weight'],
            ], $userId);
        }

        /** create and update if by user_id wise */
        $this->createORUpdateSettingProgram($input, $userId);

        return $this->getSettingProgram();
    }

    /**
     * createORUpdateSettingProgram => Create and update Setting Program
     *
     * @param  mixed $input
     * @param  mixed $userId
     * @return void
     */
    public function createORUpdateSettingProgram($input, $userId)
    {
        // $userId = $userId ?? Auth::id();
        return $this->settingTrainingRepository->updateOrCreate(
            [
                'user_id' => $userId
            ],
            $input
        );
    }

    /** Get setting Programs details
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettingProgram()
    {
        $settingProgram = $this->settingTrainingRepository->getDetailsByInput([
            'user_id' => Auth::id(),
            'relation' => ['user_detail', 'race_distance_detail'],
            'user_detail_list' => ['id', 'name', 'photo', 'is_active', 'height', 'weight'],
            'race_distance_detail_list' => ['id', 'name', 'code', 'is_active'],
            'first' => true
        ]);
        if (!$settingProgram) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'Setting Program']));
        }
        return $this->sendSuccessResponse($settingProgram, __('validation.common.details_found', ['module' => 'Setting Program']));
    }

    /**
     * Create update for premium.
     *
     * @param mixed $request
     *
     * @return void
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function createUpdateSettingPremium(Request $request)
    {
        $input = $request->all();
        $userId = Auth::id();

        /** create and update if by user_id wise */
        $settingPremium = $this->settingPremiumRepository->updateOrCreate(
            [
                'user_id' => $userId
            ],
            $input
        );

        # check for default credit card flag is true or not
        if (isset($input['is_card_default'], $input['credit_card_id']) && $input['is_card_default'] === true) {
            # first set default to false with this current user id

            $this->billingInformationRepository->updateManyWithUserId(
                [
                    'is_default' => false
                ],
                $userId
            );

            # to default first where user to default card
            $this->billingInformationRepository->updateWhere(
                [
                    'is_default' => $input['is_card_default']
                ],
                [
                    'credit_card_id' => $input['credit_card_id'],
                    'user_id' => $userId
                ]
            );

            // $this->billingInformationRepository->updateRich([
            //     'is_default' => true
            // ])
        }


        // $settingPremium
        return $this->getSettingPremium();
    }

    /**
     * getSettingPremium => get premium details
     *
     * @return object
     */
    public function getSettingPremium()
    {
        $settingPremium = $this->settingPremiumRepository->getDetailsByInput([
            'user_id' => Auth::id(),
            'relation' => ['user_detail', 'card_details'],
            'user_detail_list' => ['id', 'name', 'photo', 'is_active'],
            'first' => true
        ]);
        if (!$settingPremium) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'Setting Premium']));
        }
        $settingPremium = $settingPremium->toArray();

        $settingPremium['specialization_details'] = $this->specializationsRepository->getDetailsByInput([
            'ids' => $settingPremium['specialization_ids'],
            'is_active' => true
        ]);

        $settingPremium['language_details'] = $this->languagesRepository->getDetailsByInput([
            'ids' => $settingPremium['language_ids'],
            'is_active' => true
        ]);

        return $this->sendSuccessResponse($settingPremium, __('validation.common.details_found', ['module' => 'Setting Premium']));
    }

    /**
     * createCardForBillingInformation => Get all request
     *
     * @param mixed $request
     *
     * @return void
     */
    public function createCardForBillingInformation(Request $request)
    {
        $input = $request->all();

        $validation = $this->requiredValidation(['cards_information'], $input);
        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);

        foreach ($input['cards_information'] as $key => &$value) {
            $value['user_id'] = $input['user_id'] ?? Auth::id();
            $value['created_at'] = $this->getCurrentDateUTC();
            $value['updated_at'] = $this->getCurrentDateUTC();
        }

        /** Multiple Insert */
        $billingInformation = BillingInformation::insert($input['cards_information']);
        // $billingInformation = $this->billingInformationRepository->create($data);
        return $this->sendSuccessResponse($billingInformation, __('validation.common.created', ['module' => 'Billing information']));

        $paypalController = app(PaypalController::class);
        // dd('check paypal', $paypalController->storeCardDetailsPaypal());


        try {
            $client = new \GuzzleHttp\Client();

            // https://api.sandbox.paypal.com/v1/vault/credit-cards/
            # 1. first login to paypal account
            $response = $client->request(
                'POST',
                'https://api.sandbox.paypal.com/v1/oauth2/token',
                [
                    'auth' => [env('PAYPAL_SANDBOX_CLIENT_ID'), env('PAYPAL_SANDBOX_SECRET'),],
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded',],
                    'form_params' => ['grant_type' => 'client_credentials']
                ]
            );
            $paypalLoginDetail = json_decode($response->getBody()->getContents(), true);
            $paypalController = app(PaypalController::class);

            $createdCardDetail = $paypalController->storeCardDetailsPaypal($input, $paypalLoginDetail);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            // Log::error($exception->getLine(), $exception->getMessage());
            // dd('"Exception', $exception->getMessage());
        }

        /** first information */
        #
        foreach ($input as $key => $cardInfo) {
            #

        }
        #

    }

    /** Get All setting Training Details
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettingTrainingDetails()
    {
        $trainingRaceTime = $this->settingTrainingRepository->getDetailsByInput([
            'user_id' => Auth::id(),
            'list' => ['id', 'user_id', 'race_distance_id', 'race_time'],
            'first' => true
        ]);
        /** @var $trainingRaceTime */
        if (!$trainingRaceTime) {
            return $this->sendSuccessResponse([
                'race_distance_id' => null, 'race_time' => null
            ], __('validation.common.details_not_found', ['module' => 'Setting race time']));
        }
        return $this->sendSuccessResponse($trainingRaceTime, __('validation.common.details_not_found', ['module' => 'Setting race time']));
    }

    /** Get All Training Units Details,
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSettingTrainingUnits(): \Illuminate\Http\JsonResponse
    {
        $trainingUnitsRepository = app(TrainingSettingUnitsRepositoryEloquent::class);
        $units = $trainingUnitsRepository->getDetailsByInput([
            'is_active' => true
        ]);
        return $this->sendSuccessResponse($units, __('validation.common.details_found', ['module' => 'Training Units']));
    }
}
