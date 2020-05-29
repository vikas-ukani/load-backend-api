<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Libraries\Repositories\RegionRepositoryEloquent;
use App\Libraries\Repositories\AccountRepositoryEloquent;
use App\Libraries\Repositories\BodyPartRepositoryEloquent;
use App\Libraries\Repositories\CurrencyRepositoryEloquent;
use App\Libraries\Repositories\ServicesRepositoryEloquent;
use App\Libraries\Repositories\CountriesRepositoryEloquent;
use App\Libraries\Repositories\LanguagesRepositoryEloquent;
use App\Libraries\Repositories\MechanicsRepositoryEloquent;
use App\Libraries\Repositories\EquipmentsRepositoryEloquent;
use App\Libraries\Repositories\ActionForceRepositoryEloquent;
use App\Libraries\Repositories\TrainingGoalRepositoryEloquent;
use App\Libraries\Repositories\RepetitionMaxRepositoryEloquent;
use App\Libraries\Repositories\TrainingTypesRepositoryEloquent;
use App\Libraries\Repositories\AvailableTimesRepositoryEloquent;
use App\Libraries\Repositories\PaymentOptionsRepositoryEloquent;
use App\Libraries\Repositories\SpecializationsRepositoryEloquent;
use App\Libraries\Repositories\TargetedMusclesRepositoryEloquent;
use App\Libraries\Repositories\TrainingActivityRepositoryEloquent;
use App\Libraries\Repositories\TrainingLogStyleRepositoryEloquent;
use App\Libraries\Repositories\ProfessionalTypesRepositoryEloquent;
use App\Libraries\Repositories\TrainingFrequencyRepositoryEloquent;
use App\Libraries\Repositories\TrainingIntensityRepositoryEloquent;
use App\Libraries\Repositories\CancellationPolicyRepositoryEloquent;
use App\Libraries\Repositories\SettingRaceDistanceRepositoryEloquent;
use App\Libraries\Repositories\CustomTrainingProgramRepositoryEloquent;
use App\Libraries\Repositories\PresetTrainingProgramRepositoryEloquent;

class AllInOneController extends Controller
{
    protected $usersRepository;
    protected $regionRepository;
    protected $trainingLogStyleRepository;
    protected $accountRepository;
    protected $bodyPartRepository;
    protected $currencyRepository;
    protected $servicesRepository;
    protected $countriesRepository;
    protected $languagesRepository;
    protected $mechanicsRepository;
    protected $equipmentsRepository;
    protected $actionForceRepository;
    protected $trainingGoalRepository;
    protected $repetitionMaxRepository;
    protected $trainingTypesRepository;
    protected $availableTimesRepository;
    protected $paymentOptionsRepository;
    protected $targetedMusclesRepository;
    protected $specializationsRepository;
    protected $trainingActivityRepository;
    protected $professionalTypesRepository;
    protected $trainingIntensityRepository;
    protected $trainingFrequencyRepository;
    protected $cancellationPolicyRepository;
    protected $settingRaceDistanceRepository;
    protected $presetTrainingProgramRepository;
    protected $customTrainingProgramRepository;

    /**
     * __construct => Inject all Repos.
     *
     * @param UsersRepositoryEloquent $usersRepository
     * @param RegionRepositoryEloquent $regionRepository
     * @param AccountRepositoryEloquent $accountRepository
     * @param BodyPartRepositoryEloquent $bodyPartRepository
     * @param CurrencyRepositoryEloquent $currencyRepository
     * @param ServicesRepositoryEloquent $servicesRepository
     * @param LanguagesRepositoryEloquent $languagesRepository
     * @param CountriesRepositoryEloquent $countriesRepository
     * @param MechanicsRepositoryEloquent $mechanicsRepository
     * @param EquipmentsRepositoryEloquent $equipmentsRepository
     * @param ActionForceRepositoryEloquent $actionForceRepository
     * @param TrainingGoalRepositoryEloquent $trainingGoalRepository
     * @param RepetitionMaxRepositoryEloquent $repetitionMaxRepository
     * @param TrainingTypesRepositoryEloquent $trainingTypesRepository
     * @param PaymentOptionsRepositoryEloquent $paymentOptionsRepository
     * @param AvailableTimesRepositoryEloquent $availableTimesRepository
     * @param SpecializationsRepositoryEloquent $specializationsRepository
     * @param TargetedMusclesRepositoryEloquent $targetedMusclesRepository
     * @param TrainingActivityRepositoryEloquent $trainingActivityRepository
     * @param ProfessionalTypesRepositoryEloquent $professionalTypesRepository
     * @param TrainingIntensityRepositoryEloquent $trainingIntensityRepository
     * @param TrainingFrequencyRepositoryEloquent $trainingFrequencyRepository
     * @param CancellationPolicyRepositoryEloquent $cancellationPolicyRepository
     * @param SettingRaceDistanceRepositoryEloquent $settingRaceDistanceRepository
     * @param CustomTrainingProgramRepositoryEloquent $customTrainingProgramRepository
     * @param PresetTrainingProgramRepositoryEloquent $presetTrainingProgramRepository
     */
    public function __construct(
        UsersRepositoryEloquent $usersRepository,
        RegionRepositoryEloquent $regionRepository,
        AccountRepositoryEloquent $accountRepository,
        BodyPartRepositoryEloquent $bodyPartRepository,
        CurrencyRepositoryEloquent $currencyRepository,
        ServicesRepositoryEloquent $servicesRepository,
        LanguagesRepositoryEloquent $languagesRepository,
        CountriesRepositoryEloquent $countriesRepository,
        MechanicsRepositoryEloquent $mechanicsRepository,
        EquipmentsRepositoryEloquent $equipmentsRepository,
        ActionForceRepositoryEloquent $actionForceRepository,
        TrainingGoalRepositoryEloquent $trainingGoalRepository,
        RepetitionMaxRepositoryEloquent $repetitionMaxRepository,
        TrainingTypesRepositoryEloquent $trainingTypesRepository,
        TrainingLogStyleRepositoryEloquent $trainingLogStyleRepository,
        PaymentOptionsRepositoryEloquent $paymentOptionsRepository,
        AvailableTimesRepositoryEloquent $availableTimesRepository,
        SpecializationsRepositoryEloquent $specializationsRepository,
        TargetedMusclesRepositoryEloquent $targetedMusclesRepository,
        TrainingActivityRepositoryEloquent $trainingActivityRepository,
        ProfessionalTypesRepositoryEloquent $professionalTypesRepository,
        TrainingIntensityRepositoryEloquent $trainingIntensityRepository,
        TrainingFrequencyRepositoryEloquent $trainingFrequencyRepository,
        CancellationPolicyRepositoryEloquent $cancellationPolicyRepository,
        SettingRaceDistanceRepositoryEloquent $settingRaceDistanceRepository,
        CustomTrainingProgramRepositoryEloquent $customTrainingProgramRepository,
        PresetTrainingProgramRepositoryEloquent $presetTrainingProgramRepository
    ) {
        $this->usersRepository = $usersRepository;
        $this->regionRepository = $regionRepository;
        $this->trainingLogStyleRepository = $trainingLogStyleRepository;
        $this->accountRepository = $accountRepository;
        $this->bodyPartRepository = $bodyPartRepository;
        $this->currencyRepository = $currencyRepository;
        $this->servicesRepository = $servicesRepository;
        $this->mechanicsRepository = $mechanicsRepository;
        $this->languagesRepository = $languagesRepository;
        $this->countriesRepository = $countriesRepository;
        $this->equipmentsRepository = $equipmentsRepository;
        $this->actionForceRepository = $actionForceRepository;
        $this->trainingGoalRepository = $trainingGoalRepository;
        $this->repetitionMaxRepository = $repetitionMaxRepository;
        $this->trainingTypesRepository = $trainingTypesRepository;
        $this->availableTimesRepository = $availableTimesRepository;
        $this->paymentOptionsRepository = $paymentOptionsRepository;
        $this->targetedMusclesRepository = $targetedMusclesRepository;
        $this->specializationsRepository = $specializationsRepository;
        $this->trainingActivityRepository = $trainingActivityRepository;
        $this->professionalTypesRepository = $professionalTypesRepository;
        $this->trainingIntensityRepository = $trainingIntensityRepository;
        $this->trainingFrequencyRepository = $trainingFrequencyRepository;
        $this->cancellationPolicyRepository = $cancellationPolicyRepository;
        $this->settingRaceDistanceRepository = $settingRaceDistanceRepository;
        $this->presetTrainingProgramRepository = $presetTrainingProgramRepository;
        $this->customTrainingProgramRepository = $customTrainingProgramRepository;
    }

    /**
     * getAllDetailsDynamically => get all dropdown values from database dynamically
     *
     * @return void
     */
    public function getAllDetailsDynamically()
    {
        $allData = [
            'default_body_part_image_url_back' => env('APP_URL', url('/') . LIBRARY_BODY_PART_IMAGE_BACK_URL),
            'default_body_part_image_url_front' => env('APP_URL', url('/') . LIBRARY_BODY_PART_IMAGE_FRONT_URL)
        ];

        /** get active account details */
        // $allData['regions'] = $this->getResignsDetails();

        /** get training log styles */
        $allData['training_log_styles'] = $this->getTrainingLogStylesDetails();

        /**
         * get active account details
         */
        $allData['accounts'] = $this->getAccountDetails();

        /** get services */
        $allData['services'] = $this->getServicesDetails();

        /** get services */
        $allData['languages'] = $this->getLanguagesDetails();

        /** get active account details */
        $allData['countries'] = $this->getCountriesDetails();

        /** get mechanics details */
        $allData['mechanics'] = $this->getMechanicsDetails();

        /** get category details */
        $allData['category'] = $this->getBodyPartsDetails();

        /** get body_sub_parts details --client changed this */
        $allData['regions'] = $this->getBodySubPartsDetails();

        /** get currency details */
        $allData['currency'] = $this->getCurrencyDetails();

        /** get equipments details */
        $allData['equipments'] = $this->getEquipmentsDetails();

        /** get action_force details */
        $allData['action_force'] = $this->getActionForceDetails();

        /** get training_intensity details */
        $allData['training_intensity'] = $this->getTrainingIntensityDetails();

        /** get training goal by display at wise details */
        $allData['training_goal'] = $this->getAllTrainingGoalDetails();

        $allData['training_goal_log_cardio'] = $this->getTrainingGoalByDetails([
            'display_at' => TRAINING_GOAL_LOG_CARDIO,
            'is_display_at_exist' => true
        ]);

        $allData['training_goal_log_resistance'] = $this->getTrainingGoalByDetails([
            'display_at' => TRAINING_GOAL_LOG_RESISTANCE,
            'is_display_at_exist' => true
        ]);

        $allData['training_goal_program_cardio'] = $this->getTrainingGoalByDetails([
            'display_at' => TRAINING_GOAL_PROGRAM_CARDIO,
            'is_display_at_exist' => true
        ]);

        $allData['training_goal_program_resistance'] = $this->getTrainingGoalByDetails([
            'display_at' => TRAINING_GOAL_PROGRAM_RESISTANCE,
            'is_display_at_exist' => true
        ]);

        /** get training_types details */
        $allData['training_types'] = $this->getTrainingTypesDetails();

        /** get available_times details */
        $allData['available_times'] = $this->getAvailableTimesDetails();

        /** get payment_options details */
        $allData['payment_options'] = $this->getPaymentOptionsDetails();

        /** get repetition_max details */
        $allData['repetition_max'] = $this->getRepetitionMaxDetails();

        /** get targeted_muscles training */
        $allData['targeted_muscles'] = $this->getTargetedMusclesDetails();

        /** get specializations */
        $allData['specializations'] = $this->getSpecializationsDetails();

        /** get training_activity details */
        $allData['training_activity'] = $this->getTrainingActivityDetails();

        /** get professional_types details */
        $allData['professional_types'] = $this->getProfessionalTypesDetails();

        /** get custom_training_program */
        $allData['custom_training_program'] = $this->getCustomTrainingProgram();

        /** get cancellation_policy */
        $allData['cancellation_policy'] = $this->getCancellationPolicyDetails();

        /** get cancellation policies */
        $allData['race_distance'] = $this->getRaceDistanceDetails();

        /** get training frequency details */
        $allData['training_frequencies'] = $this->getTrainingFrequenciesDetails();

        /** get resistance preset training */
        $allData['cardio_preset_training_program'] = $this->getCardioPresetTrainingProgram();

        /** get resistance preset training */
        $allData['resistance_preset_training_program'] = $this->getResistancePresetTrainingProgram();

        /** return final response */
        return $this->sendSuccessResponse($allData, __('validation.common.details_found', ['module' => "All"]));
    }

    /**
     * getAccountDetails => get All Active Account Details
     *
     * @return void
     */
    private function getAccountDetails()
    {
        return $this->accountRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getServicesDetails => get all active custom specialization
     *
     * @return void
     */
    public function getServicesDetails()
    {
        return $this->servicesRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getLanguagesDetails()
    {
        return $this->languagesRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getCountriesDetails => get All Active Countries Details
     *
     * @return void
     */
    public function getCountriesDetails()
    {
        return $this->countriesRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getMechanicsDetails => get all active body parts
     *
     * @return void
     */
    public function getMechanicsDetails()
    {
        return $this->mechanicsRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getBodyPartsDetails => get all active body parts
     *
     * @return void
     */
    public function getBodyPartsDetails()
    {
        return $this->bodyPartRepository->getDetailsByInput(['is_parent' => true, 'is_active' => true]);
    }

    /**
     * getBodyPartsDetails => get all active body parts
     *
     * @return void
     */
    public function getBodySubPartsDetails()
    {
        return $this->bodyPartRepository->getDetailsByInput(['is_parent' => false, 'is_active' => true]);
    }

    /**
     * getBodyPartsDetails => get all active body parts
     *
     * @return void
     */
    public function getCurrencyDetails()
    {
        return $this->currencyRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getEquipmentsDetails => get all active equipments
     *
     * @return void
     */
    public function getEquipmentsDetails()
    {
        return $this->equipmentsRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getActionForceDetails => get all active body parts
     *
     * @return void
     */
    public function getActionForceDetails()
    {
        return $this->actionForceRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getTrainingIntensityDetails => get All Active Training intensity
     *
     * @return void
     */
    public function getTrainingIntensityDetails()
    {
        return $this->trainingIntensityRepository->getDetailsByInput(['sort_by' => ['sequence', 'asc'], 'is_active' => true]);
    }

    public function getAllTrainingGoalDetails()
    {
        return $this->trainingGoalRepository->getDetailsByInput([]);
    }

    /**
     * getTrainingGoalByDetails => get All Active Training Goal To Display at By Request.
     *
     * @return void
     */
    public function getTrainingGoalByDetails($input)
    {
        return $this->trainingGoalRepository->getDetailsByInput(
            array_merge($input, [
                'is_active' => true,
                'sort_by' => [
                    'sequence', 'asc'
                ]
            ])
        );
    }

    /**
     * getTrainingTypesDetails => get All Active Repetition Max
     *
     * @return void
     */
    public function getTrainingTypesDetails()
    {
        return $this->trainingTypesRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getAvailableTimesDetails => get All Active Repetition Max
     *
     * @return void
     */
    public function getAvailableTimesDetails()
    {
        return $this->availableTimesRepository->getDetailsByInput(['is_active' => true, 'sort_by' => ['id', 'asc']]);
    }

    /**
     * getAvailableTimesDetails => get All Active Repetition Max
     *
     * @return void
     */
    public function getPaymentOptionsDetails()
    {
        return $this->paymentOptionsRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getRepetitionMaxDetails => get All Active Repetition Max
     *
     * @return void
     */
    public function getRepetitionMaxDetails()
    {
        return $this->repetitionMaxRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getTargetedMusclesDetails => get all active custom targeted Muscles
     *
     * @return void
     */
    public function getTargetedMusclesDetails()
    {
        return $this->targetedMusclesRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getSpecializationsDetails => get all active custom specialization
     *
     * @return void
     */
    public function getSpecializationsDetails()
    {
        return $this->specializationsRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getTrainingActivityDetails => get All Active Training activity
     *
     * @return void
     */
    public function getTrainingActivityDetails()
    {
        return $this->trainingActivityRepository->getDetailsByInput(
            [
                'sort_by' => ['sequence', 'asc'],
                'is_active' => true
            ]
        );
    }

    /**
     * getTrainingActivityDetails => get All Active Training activity
     *
     * @return void
     */
    public function getProfessionalTypesDetails()
    {
        return $this->professionalTypesRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getCustomTrainingProgram => get all active custom training programs
     *
     * @return void
     */
    public function getCustomTrainingProgram()
    {
        return $this->customTrainingProgramRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getCancellationPolicyProgram => Get All Cancellation polices
     *
     * @return void
     */
    public function getCancellationPolicyDetails()
    {
        return $this->cancellationPolicyRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getRaceDistanceDetails => Get All Race Distance for setting training
     *
     * @return void
     */
    public function getRaceDistanceDetails()
    {
        return $this->settingRaceDistanceRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getTrainingFrequenciesDetails => get All Active Training frequency
     *
     * @return void
     */
    public function getTrainingFrequenciesDetails()
    {
        return $this->trainingFrequencyRepository->getDetailsByInput(['is_active' => true]);
    }

    /**
     * getCardioPresetTrainingProgram => Get All Active Cardio Preset Training Program
     *
     * @return void
     */
    public function getCardioPresetTrainingProgram()
    {
        return $this->presetTrainingProgramRepository->getDetailsByInput(['status' => TRAINING_PROGRAM_STATUS_CARDIO, "type" => TRAINING_PROGRAM_TYPE_PRESET, 'is_active' => true]);
    }

    /**
     * getResistancePresetTrainingProgram => Get All Active Resistance Preset Training Program
     *
     * @return void
     */
    public function getResistancePresetTrainingProgram()
    {
        return $this->presetTrainingProgramRepository->getDetailsByInput(['status' => TRAINING_PROGRAM_STATUS_RESISTANCE, "type" => TRAINING_PROGRAM_TYPE_PRESET, 'is_active' => true]);
    }

    /**
     * getResignsDetails => get All Active Account Details
     *
     * @return void
     */
    public function getResignsDetails()
    {
        return $this->regionRepository->getDetailsByInput(['is_active' => true]);
    }
    public function getTrainingLogStylesDetails()
    {
        return $this->trainingLogStyleRepository->getDetailsByInput(['is_active' => true]);
    }

    /** Update latitude and longitude
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function updateLatLongAPI(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(["latitude", "longitude", "id"], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        $user = $this->updateLatitudeAndLongitude($input);
        return $this->sendSuccessResponse(null, __("validation.common.saved", ['module' => 'Users']));
    }

    /**
     * updateLatitudeAndLongitude => to update users latitude and longitude
     *
     * @param mixed $input
     *
     * @return void
     */
    public function updateLatitudeAndLongitude($input)
    {
        if (isset($input) && isset($input['id'])) {
            return $this->usersRepository->updateRich([
                "latitude" => $input['latitude'] ?? null,
                "longitude" => $input['longitude'] ?? null
            ], $input['id'] ?? Auth::id());
        }
    }
}
