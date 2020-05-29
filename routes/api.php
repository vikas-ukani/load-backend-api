<?php

/** @noinspection ALL */
$router->group([/* 'prefix' => 'api' */], function () use ($router) {
    $router->get('/test', function () {
        dd('API route');
    });

    /**
     * Authentication related routes
     */
    $router->group(['prefix' => 'auth', 'namespace' => 'Auth'], function () use ($router) {

        $router->post('/login', "AuthController@login");
        $router->post('/sign-up', "AuthController@signUp");

        $router->post('/reset-password', "AuthController@resetPasswordFn");
        $router->post('/change-password', "AuthController@changePasswordFn");
    });

    /**
     * after login routes access
     */
    $router->group(['middleware' => ["auth:api"]], function () use ($router) {

        $router->post('update-latitude-longitude', "AllInOneController@updateLatLongAPI");

        /**
         * get all dynamic data from database
         */
        $router->get('get-all-data', "AllInOneController@getAllDetailsDynamically");

        $router->group(['namespace' => "Calender"], function () use ($router) {

            $router->get('log-cardio-validation-list', "TrainingLogController@listOfLogCardioValidations");

            /**
             * training log related routes
             */
            $router->post('create-training-log', "TrainingLogController@createTrainingLog");
            $router->get('save-is-log-flag/{id}', "TrainingLogController@saveLogFlag");
            $router->get('save-template-to-workout/{id}', "TrainingLogController@saveToTemplateAsSavedWorkout");
            $router->post('training-log-list', "TrainingLogController@getTrainingProgramAndLog");
            $router->get('get-training-log/{id}', "TrainingLogController@show");
            $router->get('training-log-delete/{id}', "TrainingLogController@destroy");
            $router->put('training-log-update/{id}', "TrainingLogController@update");
            $router->put('save-training-review/{id}', "TrainingLogController@saveTrainingLogReview");
            $router->post('save-generated-calculation', "TrainingLogController@saveGeneratedCalculations");
            $router->put('complete-training-log/{id}', "TrainingLogController@completeTrainingLog");

            /** Generate Training Summary Page API */
            $router->post('get-training-log-summary-details', "LogSummary\SummaryCalculationController@generateSummaryDetails");

            /**
             * training program related routes
             */
            $router->post('create-training-program', "TrainingProgramController1@store");
            $router->post('check-program-is-available', "TrainingProgramController1@checkProgramIsAvailableOrNotToStore");

            /** week related apis */
            $router->post('get-weeks-laps', "TrainingProgramController1@store");

            $router->post('create-week-wise-daily-programs', "WeekWiseCompletedPrograms@createWeekWiseDailyPrograms"); // NEW
            $router->put('update-week-wise-daily-programs/{id}', "WeekWiseCompletedPrograms@updateWeekWiseDailyPrograms"); // NEW
        });

        /**
         * saved workout related routes ( saved templates )
         */
        $router->group(['namespace' => "SavedWorkout"], function () use ($router) {
            $router->post('save-workout-list', "SaveWorkoutController@listDetails");
        });

        /**
         * notification related routes
         */
        $router->group(['namespace' => "Notifications"], function () use ($router) {
            $router->post('notification-list', "NotificationsController@listDetails");
            $router->get('notification-read/{id}', "NotificationsController@readNotification");
        });

        /**
         * Sessions related routes
         */
        $router->group(['namespace' => "Sessions"], function () use ($router) {
            $router->post('sessions-list', "SessionsController@listDetails");
        });

        /**
         * Library related routes
         */
        $router->group(['namespace' => "Library"], function () use ($router) {

            $router->post('log-resistance-validation-list', "LogResistanceValidationsController@listOfLogResistanceValidations");
            $router->post('library-list', "LibraryController@list");
            $router->post('library-create', "LibraryController@store");
            $router->get('library-show/{id}', "LibraryController@show");
            $router->put('library-update/{id}', "LibraryController@update");
            $router->delete('library-delete/{id}', "LibraryController@destroy");
            $router->put('library-set-favorite/{id}', "LibraryController@setToFavorite");
            $router->post('library-graph-details/{id}', "LibraryController@getGraphDetails");

            $router->post('create-update-common-library-detail', "CustomCommonLibraryDetailController@createUpdateCustomCommonLibraryDetails");
            $router->get('custom-common-library-details/{id}', "CustomCommonLibraryDetailController@show");
        });

        /**
         * messages related routes
         */
        $router->group(['namespace' => "Messages"], function () use ($router) {
            $router->post('messages-user-list', "MessagesController@getUsersList");
            $router->post('messages-create', "MessagesController@store");
            $router->get('messages-show/{id}', "MessagesController@show");
            $router->put('messages-update/{id}', "MessagesController@update");
            $router->delete('messages-delete/{id}', "MessagesController@destroy");
            $router->put('messages-set-favorite/{id}', "MessagesController@setToFavorite");
            $router->post('get-conversation-detail-custom', 'MessagesController@getConversationDetailCustomize');
        });

        /**
         * Load Center ( request and events ) related routes
         */
        $router->group(['namespace' => "LoadCenter"], function () use ($router) {
            $router->get('get-event-types-list', "LoadCenterController@getEventTypeList");
            $router->post('load-center-list', "LoadCenterController@list");
            $router->post('professional-user-list', "LoadCenterController@getProfessionalUserProfileListByInput");
            $router->post('load-center-feed-search-list', "LoadCenterController@getUsersList");
            $router->post('load-center-create', "LoadCenterController@store");
            $router->get('load-center-event-show/{id}', "LoadCenterController@eventShow");
            $router->get('load-center-request-show/{id}', "LoadCenterController@requestShow");
            $router->get('load-center-show/{id}', "LoadCenterController@show");
            $router->post('load-center-update', "LoadCenterController@update");
            $router->delete('load-center-request-delete/{id}', "LoadCenterController@destroyRequest");

            $router->post('comment-list', "FeedLikeCommentController@commentList");

            // $router->get('library-show/{id}', "LibraryController@show");
            // $router->put('library-update/{id}', "LibraryController@update");
            // $router->delete('library-delete/{id}', "LibraryController@destroy");
            // $router->put('library-set-favorite/{id}', "LibraryController@setToFavorite");

            $router->post('feed-like', "FeedLikeCommentController@likeFeed");
            $router->post('create-comment', "FeedLikeCommentController@createComment");

            /** load center feed report list */
            $router->get('load-center-feed-report-list', "ReportController@getAllReports");

            /** add users report for feed from load center module */
            $router->post('load-center-feed-add-report', "ReportController@addReportOnFeed");
        });

        $router->group(['namespace' => "Bookmark"], function () use ($router) {
            $router->post('bookmark-list', 'BookmarkController@list');
            $router->post('bookmark-list-view-all', 'BookmarkController@listViewAll');
            $router->post('create-delete-bookmark', 'BookmarkController@createOrDelete');
        });

        /**
         * Professional Profile related apis
         */
        $router->group(['namespace' => "ProfessionalProfile"], function () use ($router) {

            $router->post('create-or-update-professional-profile', "ProfessionalProfileController@store");
            $router->get('get-professional-profile-details', "ProfessionalProfileController@getLoginUserByProfessionalProfile");
            $router->post('professional-profile-list', "ProfessionalProfileController@list");
            $router->get('professional-profile-show/{id}', "ProfessionalProfileController@show");
            $router->put('professional-profile-update/{id}', "ProfessionalProfileController@update");
            $router->delete('professional-profile-delete/{id}', "ProfessionalProfileController@destroy");

            $router->post('store-request-to-make-client', "ProfessionalProfileController@storeUserBookClientRequest");
            $router->post('get-client-booked-dates', "ProfessionalProfileController@getClientBookedDate");
        });

        /**
         * User Profile related routes
         */
        $router->group(['namespace' => "Users"], function () use ($router) {
            $router->post('users-list', "UsersController@list");
            $router->post('user-update/{id}', "UsersController@update");
            $router->get('user/{id}', "UsersController@show");
            /** set user to follow and unfollowing user */
            $router->post('follow-unfollow-user', "UserFollowsController@followUnfollowUser");

            // emergency contacts
            $router->get('get-emergency-contact-details', "UserEmergencyContactsController@getContactsDetails");
            $router->post('save-contact-number', "UserEmergencyContactsController@saved");
        });

        $router->group(['namespace' => "Setting"], function ($router) {

            //            $router->get('get-emergency-contact-details', "SettingController@getEmergencyContact");

            /** Account related apis */
            $router->post('update-account-type', "AccountController@updateAccountType");
            $router->post('update-account-snooze', "AccountController@updateAccountSnooze");

            $router->post('setting-create-update-program', 'SettingController@createUpdateSettingProgram');
            $router->get('get-setting-program', 'SettingController@getSettingProgram');

            $router->get('get-setting-training-detail', "SettingController@getSettingTrainingDetails");

            $router->post('setting-create-update-primium', 'SettingController@createUpdateSettingPremium');
            $router->get('get-setting-primium', 'SettingController@getSettingPremium');

            $router->post('add-billing-information', "SettingController@createCardForBillingInformation");

            $router->get('all-training-units-list', "SettingController@getAllSettingTrainingUnits");
        });

        /**
         * Check user account { professional OR premium }
         */
        $router->group(['middleware' => "check_account"], function () use ($router) {
            $router->group(['namespace' => "Library"], function () use ($router) {
                // $router->post('library-list', "LibraryController@list");
            });
        });
    });
});
