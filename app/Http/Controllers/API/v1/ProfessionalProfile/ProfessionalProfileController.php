<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\ProfessionalProfile;

use App\Http\Controllers\Controller;
use App\Models\BookedClients;
use App\Supports\DateConvertor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Repositories\BookedClientsRepositoryEloquent;
use App\Libraries\Repositories\CountriesRepositoryEloquent;
use App\Libraries\Repositories\LanguagesRepositoryEloquent;
use App\Libraries\Repositories\MessageConversationRepositoryEloquent;
use App\Libraries\Repositories\ProfessionalProfileRepositoryEloquent;
use App\Libraries\Repositories\SpecializationsRepositoryEloquent;
use App\Libraries\Repositories\UserFollowersRepositoryEloquent;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Libraries\Repositories\UsersSnoozeRepositoryEloquent;

class ProfessionalProfileController extends Controller
{
    use DateConvertor;


    protected $moduleName = "Professional";
    protected $userId;
    protected $usersRepository;
    protected $countriesRepository;
    protected $languagesRepository;
    protected $usersSnoozeRepository;
    protected $bookedClientsRepository;
    protected $userFollowersRepository;
    protected $specializationsRepository;
    protected $messageConversationRepository;
    protected $professionalProfileRepository;

    public function __construct(
        UsersRepositoryEloquent $usersRepository,
        CountriesRepositoryEloquent $countriesRepository,
        LanguagesRepositoryEloquent $languagesRepository,
        UsersSnoozeRepositoryEloquent $usersSnoozeRepository,
        BookedClientsRepositoryEloquent $bookedClientsRepository,
        UserFollowersRepositoryEloquent $userFollowersRepository,
        SpecializationsRepositoryEloquent $specializationsRepository,
        MessageConversationRepositoryEloquent $messageConversationRepository,
        ProfessionalProfileRepositoryEloquent $professionalProfileRepository
    ) {
        $this->userId = Auth::id();
        $this->usersRepository = $usersRepository;
        $this->countriesRepository = $countriesRepository;
        $this->languagesRepository = $languagesRepository;
        $this->usersSnoozeRepository = $usersSnoozeRepository;
        $this->bookedClientsRepository = $bookedClientsRepository;
        $this->userFollowersRepository = $userFollowersRepository;
        $this->specializationsRepository = $specializationsRepository;
        $this->messageConversationRepository = $messageConversationRepository;
        $this->professionalProfileRepository = $professionalProfileRepository;
    }

    /**
     * store => Create Professional
     *
     * @param mixed $request
     *
     * @return void
     */
    public function store(Request $request)
    {
        $input = $request->all();

        /** get user id from token */
        $userId = $this->userId;

        # 1 check validation # SET VALIDATION

        /** get professional profile details by user id  */
        $profile = $this->professionalProfileRepository->getDetailsByInput([
            'user_id' => $userId,
            'first' => true
        ]);

        /** manage check availability */
        // if (isset($input['is_custom'])) {
        //         if ($input['is_custom'] == true) {
        //                 #
        //         } else {
        //                 #
        //         }
        // }

        /**  update latitude and longitude in users details  */
        if (isset($input['latitude']) && isset($input['longitude'])) {
            $this->usersRepository->updateRich(
                [
                    'latitude' => $input['latitude'],
                    'longitude' => $input['longitude']
                ],
                $userId
            );
        }

        # 2 check for profile is not found then create ele update it.
        if ($profile == null) {
            $input['user_id'] = $userId;
            # 3 if data not found then create
            $profile = $this->professionalProfileRepository->create($input);
        } else {
            # 4 else update profile data
            $profile = $this->professionalProfileRepository->updateRich(
                $input,
                $profile->id
            );
        }
        /** get same response from get show api */
        return $this->getLoginUserByProfessionalProfile();
    }

    /** get professional user profile by login user id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function getLoginUserByProfessionalProfile()
    {
        $professionalProfile = $this->professionalProfileRepository->getDetailsByInput([
            'user_id' => Auth::id(),
            'relation' => ["cancellation_policy_detail", "currency_detail", "payment_option_detail", "user_detail", "professional_type_detail"],
            "cancellation_policy_detail_list" => ["id", "name", "code", "description", "is_active"],
            "currency_detail_list" => ["id", "name", "code", "is_active"],
            "user_detail_list" => ["id", "name", "latitude", "longitude"],
            'first' => true
        ]);

        /** return null when profiles not found */
        if (!!!$professionalProfile) {
            return $this->sendBadRequest($professionalProfile, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        $professionalProfile = $professionalProfile->toArray();

        /** get specialization details */
        $professionalProfile['specialization_details'] = $this->specializationsRepository->getDetailsByInput([
            'ids' => $professionalProfile['specialization_ids']
        ]);

        /** get languages spoken details */
        $professionalProfile['languages_spoken_details'] = $this->languagesRepository->getDetailsByInput([
            'ids' => $professionalProfile['languages_spoken_ids'],
            "list" => ["id", "name", "is_active"]
        ])->toArray();

        /** get languages written details */
        $professionalProfile['languages_written_details'] = $this->languagesRepository->getDetailsByInput([
            'ids' => $professionalProfile['languages_written_ids'],
            "list" => ["id", "name", "is_active"]
        ])->toArray();

        return $this->sendSuccessResponse($professionalProfile, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /** Get Professional Profile User Details By Professional Profile Id,
     * @param $id => professional profile id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function show($id)
    {
        /** get event details  */
        $professionalProfile = $this->professionalProfileRepository->getDetailsByInput(
            [
                'relation' => ["cancellation_policy_detail", "currency_detail", "payment_option_detail", "user_detail", "professional_type_detail"],
                "cancellation_policy_detail_list" => ["id", "name", "code", "description", "is_active"],
                "currency_detail_list" => ["id", "name", "code", "is_active"],
                "payment_option_detail" => ["id", "name", "code", "description", "is_active"],
                "professional_type_detail" => ["id", "name", "code", "description", "is_active"],
                "user_detail_list" => ["id", "name", "country_code", "country_id", "latitude", "longitude", "mobile", "gender", "photo", "goal", "user_type", "is_active", "is_profile_complete", 'is_snooze'],
                "cancellation_policy_detail_list" => ["id", "name", "code", "description", "is_active"],
                'id' => $id,
                'first' => true
            ]
        );

        /**  check event is found or not */
        if (!!!$professionalProfile) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $professionalProfile = $professionalProfile->toArray();

        /** check for account is snooze or not */
        if (isset($professionalProfile['user_detail']) && isset($professionalProfile['user_detail']['is_snooze']) && $professionalProfile['user_detail']['is_snooze'] == true) {
            return $this->sendBadRequest(null, __('validation.common.professional_profile_is_in_snooze_mode'));
        }

        /** add country detail in user_detail */
        if (isset($professionalProfile['user_detail'])) {
            $professionalProfile['user_detail']['country_detail'] = $this->countriesRepository->getDetailsByInput([
                'id' => $professionalProfile['user_detail']['country_id'],
                'list' => ['name', 'is_active'], 'is_active' => true, 'first' => true
            ]);
        }

        $professionalProfile['specialization_details'] = $this->specializationsRepository->getDetailsByInput([
            'ids' => $professionalProfile['specialization_ids'],
            'list' => ['id', 'name', 'is_active']
        ])->toArray();

        $professionalProfile['languages_spoken_details'] = $this->languagesRepository->getDetailsByInput([
            'ids' => $professionalProfile['languages_spoken_ids'],
            'list' => ['id', 'name', 'is_active']
        ])->toArray();

        $professionalProfile['languages_written_details'] = $this->languagesRepository->getDetailsByInput([
            'ids' => $professionalProfile['languages_written_ids'],
            'list' => ['id', 'name', 'is_active']
        ])->toArray();

        /** get conversation details */
        if (isset($professionalProfile['user_id'])) {
            $professionalProfile['conversation_detail'] = $this->messageConversationRepository->getDetailsByInput([
                'from_ids' => [$this->userId, $professionalProfile['user_id']],
                'to_ids' => [$this->userId, $professionalProfile['user_id']],
                'list' => ['id'],
                'first' => true
            ]);
        }

        /** check is follow or not */
        $professionalProfile['is_following'] = $this->checkIsFollowUser($professionalProfile);

        /** Get Nearest Profiles */
        $nearestUser = $this->professionalProfileRepository->getNearestAreaProfiles(
            [
                'latitude' => $professionalProfile['user_detail']['latitude'],
                'longitude' => $professionalProfile['user_detail']['longitude'],
            ]
        );

        $userIds = array_values(collect($nearestUser)->where('distance', '<', 1000)->pluck('id')->all());

        /** get all snoozed id and find where not in professionals user id */
        $snoozedUserIds = $this->usersSnoozeRepository->getDetailsByInput(['list' => ['id', 'user_id']]);
        if (isset($snoozedUserIds)) {
            $snoozedUserIds = collect($snoozedUserIds)->pluck('user_id')->all();

            /** NOTE remove id from $userIds where id is snoozed profile */
            $userIds = array_values(array_diff($userIds, $snoozedUserIds));
        }

        if (isset($nearestUser)) {
            $professionalProfile['nearest_professional_profile'] = $this->professionalProfileRepository->getDetailsByInput([
                /** to get 10 id only in nearest area wise */
                'user_ids' => isset($userIds) ? array_slice($userIds, 0, 10) : [],
                // 'expect_user_ids' => $snoozedUserIds ?? [],
                'list' => ['id', 'user_id', 'rate'],
                'relation' => [
                    'user_detail',
                    /** 'specialization_details' */
                ],
                'user_detail_list' => ['id', 'name', 'email', 'photo', 'country_id', 'email_verified_at'],
                'nearest_limit' => 10,
            ]);
        }
        return $this->sendSuccessResponse($professionalProfile, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * checkIsFollowUser => check for user following or not, and return BOOLEAN
     *
     * @param mixed $profile
     *
     * @return void
     */
    public function checkIsFollowUser($profile)
    {
        $followingData = $this->userFollowersRepository->getDetailsByInput([
            'user_id' => $this->userId,
            'first' => true
        ]);
        if (!!!isset($followingData)) {
            return false;
        } else {
            $followingData = $followingData->toArray();
            if (isset($followingData['following_ids'])) {
                if (in_array($profile['user_id'], $followingData['following_ids'])) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * storeUserBookClientRequest => To Send request with professional user to make a client
     *
     * @param mixed $request
     *
     * @return void
     */
    public function storeUserBookClientRequest(Request $request)
    {
        $input = $request->all();

        /** check if from id not send from request then use login id  */
        if (!isset($input['from_id'])) {
            $input['from_id'] = $this->userId;
        }

        /** convert iso string date to UTC format */
        if (isset($input['selected_date'])) {
            $input['selected_date'] = $this->isoToUTCFormat($input['selected_date']);
        }

        /** check model validation */
        $validation = BookedClients::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        $bookedClient = $this->bookedClientsRepository->create($input);
        return $this->sendSuccessResponse($bookedClient, __('validation.common.created', ['module' => 'client booking']));
    }

    /**
     * getClientBookedDate => get booked details using from and to dates
     *
     * @param mixed $request
     *
     * @return void
     */
    public function getClientBookedDate(Request $request)
    {
        $input = $request->all();

        $validation = $this->requiredValidation(['from_date', 'to_date'], $input);
        if (isset($validation) && $validation['flag'] === false)   return $this->sendBadRequest(null, $validation['message']);

        /** convert date to utc format */
        if (isset($input['from_date'])) {
            $input['start_date'] = $this->isoToUTCFormat($input['from_date']);
            unset($input['from_date']);
        }
        /** convert date to utc format */
        if (isset($input['to_date'])) {
            $input['end_date'] = $this->isoToUTCFormat($input['to_date']);
            unset($input['to_date']);
        }

        $getBookedDates = $this->bookedClientsRepository->getDetails($input);

        if (isset($getBookedDates) && count($getBookedDates) == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'booked client']));
        }
        return $this->sendSuccessResponse($getBookedDates, __('validation.common.details_found', ['module' => 'booked client']));
    }
}
