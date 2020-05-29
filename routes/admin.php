<?php



// $router->group(['middleware' => ['cors']], function ($router) {
//     $router->post('user-profile', "Users\UsersController@profileUpdate");
// });

$router->get('/', function () {
    return view('admin');
});



// $router->group(['middleware' => ['cors']], function ($router) {
$router->group(['namespace' => 'Auth'], function ($router) {
    $router->post('login', "AuthController@login");
    $router->post('forgot-password', "AuthController@forgotPassword");
    $router->group(['middleware' => ["auth:api"]], function ($router) {
        $router->post('reset-password',  "AuthController@resetPasswordFn");
        $router->post('change-possword', "AuthController@changePasswordFn");
    });
});

$router->group(['middleware' => ["auth:api",]], function ($router) {
    // $router->group(['middleware' => ['cors']], function ($router) {
    //     $router->post('user-profile', "Users\UsersController@profileUpdate");
    // });
    $router->group(['namespace' => "Users"], function ($router) {
        $router->post('users-list', "UsersController@list");
        $router->get('user/{id}', "UsersController@show");
        $router->post('user-profile', "UsersController@profileUpdate");
        $router->post('users-status-change', "UsersController@statusChange");
    });

    // $router->get('/get-user-count-by-type', "AllInOneController@getUserCountsByUserType");

    /** all masters apis */
    $router->group(['namespace' => "Masters"], function ($router) {

        /**
         * All Action Force APis
         */
        $router->post('action-force-list', "ActionForceController@list");
        $router->post('action-force-create', "ActionForceController@store");
        $router->put('action-force-update/{id}', "ActionForceController@update");
        $router->get('action-force-show/{id}', "ActionForceController@show");
        $router->delete('action-force-delete/{id}', "ActionForceController@destroy");
        $router->post('action-force-status-change', "ActionForceController@statusChange");


        /**
         * All Body Parts APis
         */
        $router->post('body-part-list', "BodyPartController@list");
        $router->post('body-part-create', "BodyPartController@store");
        $router->put('body-part-update/{id}', "BodyPartController@update");
        $router->get('body-part-show/{id}', "BodyPartController@show");
        $router->delete('body-part-delete/{id}', "BodyPartController@destroy");
        $router->post('body-part-status-change', "BodyPartController@statusChange");


        /**
         * All Body Parts APis
         */
        $router->post('cancellation-policy-list', "CancellationPolicyController@list");
        $router->post('cancellation-policy-create', "CancellationPolicyController@store");
        $router->put('cancellation-policy-update/{id}', "CancellationPolicyController@update");
        $router->get('cancellation-policy-show/{id}', "CancellationPolicyController@show");
        $router->delete('cancellation-policy-delete/{id}', "CancellationPolicyController@destroy");
        $router->post('cancellation-policy-status-change', "CancellationPolicyController@statusChange");


        /**
         * All Accounts APis
         */
        $router->post('accounts-list', "AccountController@list");
        $router->post('accounts-create', "AccountController@store");
        $router->put('accounts-update/{id}', "AccountController@update");
        $router->get('accounts-show/{id}', "AccountController@show");
        $router->delete('accounts-delete/{id}', "AccountController@destroy");
        $router->post('accounts-status-change', "AccountController@statusChange");
        $router->post('accounts-status-delete-many', "AccountController@DeleteMany");

        /**
         * All countries APis
         */
        $router->post('countries-list', "CountriesController@list");
        $router->post('countries-create', "CountriesController@store");
        $router->put('countries-update/{id}', "CountriesController@update");
        $router->get('countries-show/{id}', "CountriesController@show");
        $router->delete('countries-delete/{id}', "CountriesController@destroy");
        $router->post('countries-status-change', "CountriesController@statusChange");
        $router->post('countries-status-delete-many', "CountriesController@DeleteMany");

        /**
         * All currency APis
         */
        $router->post('currency-list', "CurrencyController@list");
        $router->post('currency-create', "CurrencyController@store");
        $router->put('currency-update/{id}', "CurrencyController@update");
        $router->get('currency-show/{id}', "CurrencyController@show");
        $router->delete('currency-delete/{id}', "CurrencyController@destroy");
        $router->post('currency-status-change', "CurrencyController@statusChange");
        $router->post('currency-status-delete-many', "CurrencyController@DeleteMany");

        /**
         * All equipments APis
         */
        $router->post('equipments-list', "EquipmentsController@list");
        $router->post('equipments-create', "EquipmentsController@store");
        $router->put('equipments-update/{id}', "EquipmentsController@update");
        $router->get('equipments-show/{id}', "EquipmentsController@show");
        $router->delete('equipments-delete/{id}', "EquipmentsController@destroy");
        $router->post('equipments-status-change', "EquipmentsController@statusChange");
        $router->post('equipments-status-delete-many', "EquipmentsController@DeleteMany");

        /**
         * All languages APis
         */
        $router->post('languages-list', "LanguagesController@list");
        $router->post('languages-create', "LanguagesController@store");
        $router->put('languages-update/{id}', "LanguagesController@update");
        $router->get('languages-show/{id}', "LanguagesController@show");
        $router->delete('languages-delete/{id}', "LanguagesController@destroy");
        $router->post('languages-status-change', "LanguagesController@statusChange");
        $router->post('languages-status-delete-many', "LanguagesController@DeleteMany");

        /**
         * All Machines APis
         */
        $router->post('machines-list', "MachinesController@list");
        $router->post('machines-create', "MachinesController@store");
        $router->put('machines-update/{id}', "MachinesController@update");
        $router->get('machines-show/{id}', "MachinesController@show");
        $router->delete('machines-delete/{id}', "MachinesController@destroy");
        $router->post('machines-status-change', "MachinesController@statusChange");
        $router->post('machines-status-delete-many', "MachinesController@DeleteMany");

        /**
         * All regions APis
         */
        $router->post('regions-list', "RegionsController@list");
        $router->post('regions-create', "RegionsController@store");
        $router->put('regions-update/{id}', "RegionsController@update");
        $router->get('regions-show/{id}', "RegionsController@show");
        $router->delete('regions-delete/{id}', "RegionsController@destroy");
        $router->post('regions-status-change', "RegionsController@statusChange");
        $router->post('regions-status-delete-many', "RegionsController@DeleteMany");

        /**
         * All repetition-max APis
         */
        $router->post('repetition-max-list', "RepetitionMaxController@list");
        $router->post('repetition-max-create', "RepetitionMaxController@store");
        $router->put('repetition-max-update/{id}', "RepetitionMaxController@update");
        $router->get('repetition-max-show/{id}', "RepetitionMaxController@show");
        $router->delete('repetition-max-delete/{id}', "RepetitionMaxController@destroy");
        $router->post('repetition-max-status-change', "RepetitionMaxController@statusChange");
        $router->post('repetition-max-status-delete-many', "RepetitionMaxController@DeleteMany");

        /**
         * All services APis
         */
        $router->post('services-list', "ServicesController@list");
        $router->post('services-create', "ServicesController@store");
        $router->put('services-update/{id}', "ServicesController@update");
        $router->get('services-show/{id}', "ServicesController@show");
        $router->delete('services-delete/{id}', "ServicesController@destroy");
        $router->post('services-status-change', "ServicesController@statusChange");
        $router->post('services-status-delete-many', "ServicesController@DeleteMany");

        /**
         * All specializations APis
         */
        $router->post('specializations-list', "SpecializationsController@list");
        $router->post('specializations-create', "SpecializationsController@store");
        $router->put('specializations-update/{id}', "SpecializationsController@update");
        $router->get('specializations-show/{id}', "SpecializationsController@show");
        $router->delete('specializations-delete/{id}', "SpecializationsController@destroy");
        $router->post('specializations-status-change', "SpecializationsController@statusChange");
        $router->post('specializations-status-delete-many', "SpecializationsController@DeleteMany");

        /**
         * All specializations APis
         */
        $router->post('targeted-muscles-list', "TargetedMusclesController@list");
        $router->post('targeted-muscles-create', "TargetedMusclesController@store");
        $router->put('targeted-muscles-update/{id}', "TargetedMusclesController@update");
        $router->get('targeted-muscles-show/{id}', "TargetedMusclesController@show");
        $router->delete('targeted-muscles-delete/{id}', "TargetedMusclesController@destroy");
        $router->post('targeted-muscles-status-change', "TargetedMusclesController@statusChange");
        $router->post('targeted-muscles-status-delete-many', "TargetedMusclesController@DeleteMany");

        /**
         * All training-activities APis
         */
        $router->post('training-activities-list', "TrainingActivitiesController@list");
        $router->post('training-activities-create', "TrainingActivitiesController@store");
        $router->post('training-activities-update/{id}', "TrainingActivitiesController@update");
        $router->get('training-activities-show/{id}', "TrainingActivitiesController@show");
        $router->delete('training-activities-delete/{id}', "TrainingActivitiesController@destroy");
        $router->post('training-activities-status-change', "TrainingActivitiesController@statusChange");
        $router->post('training-activities-status-delete-many', "TrainingActivitiesController@DeleteMany");

        /**
         * All targeted-frequencies APis
         */
        $router->post('targeted-frequencies-list', "TargetedFrequenciesController@list");
        $router->post('targeted-frequencies-create', "TargetedFrequenciesController@store");
        $router->put('targeted-frequencies-update/{id}', "TargetedFrequenciesController@update");
        $router->get('targeted-frequencies-show/{id}', "TargetedFrequenciesController@show");
        $router->delete('targeted-frequencies-delete/{id}', "TargetedFrequenciesController@destroy");
        $router->post('targeted-frequencies-status-change', "TargetedFrequenciesController@statusChange");
        $router->post('targeted-frequencies-status-delete-many', "TargetedFrequenciesController@DeleteMany");

        /**
         * All training-goals APis
         */
        $router->post('training-goals-list', "TrainingGoalsController@list");
        $router->post('training-goals-create', "TrainingGoalsController@store");
        $router->put('training-goals-update/{id}', "TrainingGoalsController@update");
        $router->get('training-goals-show/{id}', "TrainingGoalsController@show");
        $router->delete('training-goals-delete/{id}', "TrainingGoalsController@destroy");
        $router->post('training-goals-status-change', "TrainingGoalsController@statusChange");
        $router->post('training-goals-status-delete-many', "TrainingGoalsController@DeleteMany");

        /**
         * All training-intensity APis
         */
        $router->post('training-intensity-list', "TrainingIntensityController@list");
        $router->post('training-intensity-create', "TrainingIntensityController@store");
        $router->put('training-intensity-update/{id}', "TrainingIntensityController@update");
        $router->get('training-intensity-show/{id}', "TrainingIntensityController@show");
        $router->delete('training-intensity-delete/{id}', "TrainingIntensityController@destroy");
        $router->post('training-intensity-status-change', "TrainingIntensityController@statusChange");
        $router->post('training-intensity-status-delete-many', "TrainingIntensityController@DeleteMany");

        /**
         * All training-intensity APis
         */
        $router->post('training-type-list', "TrainingTypeController@list");
        $router->post('training-type-create', "TrainingTypeController@store");
        $router->put('training-type-update/{id}', "TrainingTypeController@update");
        $router->get('training-type-show/{id}', "TrainingTypeController@show");
        $router->delete('training-type-delete/{id}', "TrainingTypeController@destroy");
        $router->post('training-type-status-change', "TrainingTypeController@statusChange");
        $router->post('training-type-status-delete-many', "TrainingTypeController@DeleteMany");

        /**
         * Preset programs weeks related apis
         */
        $router->post('preset-programs-weeks-list', "PresetProgramsWeeksController@list");
        $router->post('preset-programs-weeks-create', "PresetProgramsWeeksController@store");
        $router->put('preset-programs-weeks-update/{id}', "PresetProgramsWeeksController@update");
        $router->get('preset-programs-weeks-show/{id}', "PresetProgramsWeeksController@show");
        $router->delete('preset-programs-weeks-delete/{id}', "PresetProgramsWeeksController@destroy");
        $router->post('preset-programs-weeks-status-change', "PresetProgramsWeeksController@statusChange");
        $router->post('preset-programs-weeks-status-delete-many', "PresetProgramsWeeksController@DeleteMany");
        $router->post('preset-programs-weeks-update-sequence', "PresetProgramsWeeksController@UpdateSequenceMany");

        /**
         * Preset programs weeks Laps related apis
         */
        $router->post('preset-programs-weeks-laps-list', "PresetProgramsWeeksLapsController@list");
        $router->post('preset-programs-weeks-laps-create', "PresetProgramsWeeksLapsController@store");
        $router->put('preset-programs-weeks-laps-update/{id}', "PresetProgramsWeeksLapsController@update");
        $router->get('preset-programs-weeks-laps-show/{id}', "PresetProgramsWeeksLapsController@show");
        $router->delete('preset-programs-weeks-laps-delete/{id}', "PresetProgramsWeeksLapsController@destroy");
        $router->post('preset-programs-weeks-laps-status-change', "PresetProgramsWeeksLapsController@statusChange");
        $router->post('preset-programs-weeks-laps-status-delete-many', "PresetProgramsWeeksLapsController@DeleteMany");
        $router->post('preset-programs-weeks-laps-update-sequence', "PresetProgramsWeeksLapsController@UpdateSequenceMany");

        /** Training Log Reported Module From Load center Feed */
        $router->post('load-center-feed-report', "PresetProgramsWeeksLapsController@list");
    });
});
