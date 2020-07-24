<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\LoadCenter;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ImageHelperController;
use App\Libraries\Repositories\EventTypeRepositoryEloquent;
use App\Libraries\Repositories\FeedCommentsRepositoryEloquent;
use App\Libraries\Repositories\LoadCenterEventRepositoryEloquent;
use App\Libraries\Repositories\LoadCenterRequestRepositoryEloquent;
use App\Libraries\Repositories\ProfessionalProfileRepositoryEloquent;
use App\Libraries\Repositories\SpecializationsRepositoryEloquent;
use App\Libraries\Repositories\TrainingLogRepositoryEloquent;
use App\Libraries\Repositories\TrainingTypesRepositoryEloquent;
use App\Libraries\Repositories\UserFollowersRepositoryEloquent;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Libraries\Repositories\UsersSnoozeRepositoryEloquent;
use App\Models\LoadCenterEvent;
use App\Models\LoadCenterRequest;
use App\Supports\DateConvertor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoadCenterController extends Controller
{
    use DateConvertor;

    // modules name
    protected $moduleFeed = "Feed";
    protected $moduleRequest = "Request";
    protected $moduleEvent = "Event";
    protected $moduleList = "List";
    protected $moduleUser = "Users";

    protected $userId;

    protected $imageController;
    protected $feedCommentsRepository;
    protected $eventTypeRepository;
    protected $usersRepositoryEloquent;
    protected $usersSnoozeRepository;
    protected $userFollowersRepository;
    protected $trainingTypesRepository;
    protected $specializationsRepository;
    protected $loadCenterEventRepository;
    protected $loadCenterRequestRepository;
    protected $professionalProfileRepository;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        ImageHelperController $imageController,
        UsersRepositoryEloquent $usersRepositoryEloquent,
        EventTypeRepositoryEloquent $eventTypeRepository,
        UsersSnoozeRepositoryEloquent $usersSnoozeRepository,
        FeedCommentsRepositoryEloquent $feedCommentsRepository,
        UserFollowersRepositoryEloquent $userFollowersRepository,
        TrainingTypesRepositoryEloquent $trainingTypesRepository,
        LoadCenterEventRepositoryEloquent $loadCenterEventRepository,
        SpecializationsRepositoryEloquent $specializationsRepository,
        LoadCenterRequestRepositoryEloquent $loadCenterRequestRepository,
        ProfessionalProfileRepositoryEloquent $professionalProfileRepository
    ) {
        $this->userId = Auth::id();

        $this->imageController = $imageController;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->usersSnoozeRepository = $usersSnoozeRepository;
        $this->feedCommentsRepository = $feedCommentsRepository;
        $this->trainingTypesRepository = $trainingTypesRepository;
        $this->usersRepositoryEloquent = $usersRepositoryEloquent;
        $this->userFollowersRepository = $userFollowersRepository;
        $this->specializationsRepository = $specializationsRepository;
        $this->loadCenterEventRepository = $loadCenterEventRepository;
        $this->loadCenterRequestRepository = $loadCenterRequestRepository;
        $this->professionalProfileRepository = $professionalProfileRepository;
    }

    /**
     * list => get details using status wise
     *
     * @param mixed $request
     *
     * @return void
     */
    public function list(Request $request)
    {
        $input = $request->all();

        $validation = $this->requiredValidation(['status'], $input);
        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);

        // check status wise listing details
        if (isset($input['status']) && $input['status'] === LOAD_CENTER_STATUS_FEED) {
            $response = $this->feedListingFn($input);
            if (isset($response) && $response['flag'] === false) return $this->sendBadRequest(null, $response['message']);
            return $this->sendSuccessResponse($response['data'], $response['message']);
        } else if (isset($input['status']) && $input['status'] === LOAD_CENTER_STATUS_LISTING) {
            $response = $this->loadCenterListing($input);
            if (isset($response) && $response['flag'] === false) return $this->sendBadRequest(null, $response['message']);
            return $this->sendSuccessResponse($response['data'], $response['message']);
        } else if (isset($input['status']) && $input['status'] === LOAD_CENTER_STATUS_EVENT) {
            $response = $this->eventListingFn($input);
            if (isset($response) && $response['flag'] === false) return $this->sendBadRequest(null, $response['message']);
            return $this->sendSuccessResponse($response['data'], $response['message']);
        }
        return $this->sendBadRequest(null, __('validation.common.invalid_key', ['key' => "status"]));
    }

    /**
     * feedListingFn => get listing from training log where completed training log
     *
     * @param mixed $input
     *
     * @return void
     */
    public function feedListingFn($input)
    {
        $dummyInput = $input;
        unset($dummyInput['status']);
        $dummyInput['is_completed'] = true;

        /** search from user name */
        if (isset($dummyInput['search'])) {
            $userIds = $this->usersRepositoryEloquent->getDetailsByInput(["search" => $dummyInput['search'], "list" => ["id"]]);
            $userIds = collect($userIds)->pluck("id")->all();
            if (isset($userIds) && count($userIds) > 0) $dummyInput['user_ids'] = $userIds;
        }

        /** get all following users ids */
        $followedUsers = $this->userFollowersRepository->getDetailsByInput([
            'user_id' => $this->userId,
            'first' => true
        ]);
        if (!isset($followedUsers)) {
            return $this->makeError(null, __('validation.common.details_not_found', ['module' => $this->moduleFeed]));
        }

        $followedUsers = $followedUsers->toArray();
        if (!isset($followedUsers) || isset($followedUsers['following_ids']) === null) {
            return $this->makeError(null, __('validation.common.details_not_found', ['module' => $this->moduleFeed]));
        }

        # set ids to $userIds and merge it
        if (isset($userIds) && is_array($userIds)) {
            #pass
            array_merge($userIds, $followedUsers['following_ids']);
            $userIds = array_filter($userIds);
        } else {
            #pass
            $userIds = $followedUsers['following_ids'];
        }

        if (isset($userIds) && count($userIds) > 0) $dummyInput['user_ids'] = $userIds;
        $response = app(TrainingLogRepositoryEloquent::class)->getDetails($dummyInput);
        if (isset($response) && $response['count'] == 0) {
            return $this->makeError(null, __('validation.common.details_found', ['module' => $this->moduleFeed]));
        }
        # add key for feed is liked and set liked user images
        $response['list'] = $this->setLikeKeyAndImage($response);

        return $this->makeResponse($response, __('validation.common.details_found', ['module' => $this->moduleFeed]));
    }

    /**
     * setLikeKeyAndImage => set images for last five liked ids
     *
     * @param mixed $response
     *
     * @return void
     */
    public function setLikeKeyAndImage($response)
    {
        $response['list'] = collect($response['list']->toArray())->map(function ($list) {
            /** check for user ids  */
            if (isset($list) && isset($list['liked_detail']) && $list['liked_detail']['user_ids']) {
                $list['liked_detail']['is_liked'] = false;
                if (in_array($this->userId, (array) $list['liked_detail']['user_ids'])) $list['liked_detail']['is_liked'] = true;
                $lastFiveIds = array_slice((array) $list['liked_detail']['user_ids'], -5);
                /** check for last five ids */
                if (isset($lastFiveIds)) {
                    $list['liked_detail']['images'] = collect($lastFiveIds)->map(function ($id) {
                        return $this->usersRepositoryEloquent->getDetailsByInput([
                            'id' => $id,
                            'list' => ["id", "photo"],
                            'first' => true
                        ]);
                    });
                }
            } else {
                $list['liked_detail']['is_liked'] = false;
                $list['liked_detail']['images'] = [];
                $list['liked_detail']['user_ids'] = [];
                $list['liked_detail']['feed_id'] = $list['id'] ?? null;
            }

            # set comment count
            if (isset($list['id'])) {
                $list['comment_count'] = $this->feedCommentsRepository->getCommentCount($list['id']);
            }

            return $list;
        });
        return $response['list'];
    }

    /**
     * loadCenterListing => Load Center Listing For Request and professional users listing by specialization grouping
     *
     * @param mixed $input
     *
     * @return mixed
     */
    public function loadCenterListing($input = null)
    {
        $userAccountTypeCode = Auth::user()->account_detail->code;
        /** only Professional User can see all of the request. But Not their own. 
         * For Professional to see their Own request, he needs to switch the view to Premium User 
         */
        // $userAccountTypeCode = 'PROFESSIONAL'; // Testing purpose only
        if ($userAccountTypeCode == ACCOUNT_TYPE_PROFESSIONAL) {
            $userID = $this->userId;
        } else {
            $userID = null;
        }
        $requestInput = [
            'list' => ["id", 'user_id', 'country_id', 'title', "specialization_ids", "start_date", "yourself", "training_type_ids"],
            'relation' => ['country_detail'],
            'country_detail_list' => ['id', 'name']
        ];
        if (isset($userID)) $requestInput['expect_user_ids'] = [$userID];
        // dd('asd', $userAccountTypeCode, $userID, $requestInput);

        /** get Event Request Listing */
        $loadCenterRequests = $this->loadCenterRequestRepository->getDetailsByInput($requestInput);
        $response['request_list'] = $loadCenterRequests ?? [];
        /** get custom relation using collection mapping */
        if (isset($response['request_list']) && count($response['request_list']) > 0) {
            $response['request_list'] = collect($response['request_list']->toArray())->map(function ($list) {
                /** to get an training type details with multiple */
                if (isset($list['training_type_ids']) && count($list['training_type_ids']) > 0) {
                    /** get training details using multiple ids */
                    $list['training_type_details'] = $this->trainingTypesRepository->getDetailsByInput(["ids" => $list['training_type_ids'], "list" => ["id", "name"]]);
                    // $list['training_type_details'] = collect($list['training_type_ids'])->map(function ($training) {
                    //     if (isset($training) && $training > 0) return $this->trainingTypesRepository->find($training)->toArray();
                    // })->all();
                }
                if (isset($list['specialization_ids'])) $list['specialization_details'] = $this->specializationsRepository->getDetailsByInput(["ids" => $list['specialization_ids'], "list" => ["id", "name"]]);
                if (isset($list['user_id'])) {
                    $list['user_detail'] = $this->usersRepositoryEloquent->getDetailsByInput([
                        'id' => $list['user_id'],
                        'first' => true,
                        'list' => ['id', 'name', "photo"]
                    ])->toArray();
                }
                return $list;
            })->all();
        }

        /** get all specialization */
        // $specializations = app(AllInOneController::class)->getSpecializationsDetails();
        $specializationRequest = ['ids' => $input['specialization_ids'] ?? null, 'is_active' => true];

        /** search in specialization records */
        // if (isset($input['search'])) {
        //     $specializationRequest['search'] = $input['search'];
        // }
        /** get specialization details using their group wise profiles details */
        $specializations = $this->specializationsRepository->getDetailsByInput($specializationRequest);
        if (isset($specializations) && $specializations->count() > 0) {
            $profileResponseGroupWise = collect($specializations)->map(function ($list) use ($input) {
                $requestInput = $input;
                $requestInput['specialization_id'] = $list->id;
                if (isset($requestInput['specialization_ids']) && count($requestInput['specialization_ids']) > 0) {
                    unset($requestInput['specialization_ids']);
                }

                /** if user can search applied then apply for users name wise searching using id  */
                if (isset($requestInput['search']) || isset($requestInput['country_ids']) || isset($requestInput['gender'])) {
                    $userRequest = [
                        'is_except_current_user' => true,
                        // 'search' => $requestInput['search'],
                        'list' => ["id"]
                    ];
                    /** get user id regarding location filter */
                    if (isset($requestInput['search'])) {
                        $userRequest['search'] = $requestInput['search'];
                    }
                    /** get user id regarding location filter */
                    if (isset($requestInput['country_ids'])) {
                        $userRequest['country_ids'] = $requestInput['country_ids'];
                    }
                    if (isset($requestInput['gender'])) {
                        $userRequest['gender'] = $requestInput['gender'];
                    }
                    /** get users details with searching user */
                    $userIds = $this->usersRepositoryEloquent->getDetailsByInput($userRequest);
                    /** get multiple user ids */
                    if (isset($userIds) && $userIds->count() > 0) $requestInput['user_ids'] = collect($userIds)->pluck('id')->all();
                }

                /** get all active and un-snoozed users ids */
                $getUserIds = $this->usersRepositoryEloquent->getDetailsByInput([
                    'is_snooze' => false,
                    'is_except_current_user' => true,
                    'is_active' => true,
                    'list' => ['id']
                ]);
                if (isset($getUserIds)) {
                    $userIds = collect($getUserIds)->pluck('id')->toArray();
                    /** check for already searching ids found then merge both ids */
                    if (isset($requestInput['user_ids'])) {
                        $requestInput['user_ids'] = array_merge($requestInput['user_ids'], $userIds);
                    } else {
                        /** else set to all un-snoozed users ids */
                        $requestInput['user_ids'] = $userIds;
                    }
                }

                /** get all snoozed id and find where not in professionals user id */
                $snoozedUserIds = $this->usersSnoozeRepository->getDetailsByInput(['list' => ['id', 'user_id']]);
                if (isset($snoozedUserIds)) {
                    $snoozedUserIds = collect($snoozedUserIds)->pluck('user_id')->all();
                    // $requestInput['expect_user_ids'] = array_merge($snoozedUserIds, $this->userId);
                    $requestInput['expect_user_ids'] = $snoozedUserIds;
                }

                if (is_array($requestInput['expect_user_ids'])) array_push($requestInput['expect_user_ids'], $this->userId);
                /** get professional profile details */
                $data = $this->professionalProfileRepository->getDetailsByInput(
                    $requestInput
                    // [
                    //     "relation" => ["user_detail"],
                    //     "user_detail_list" => ["id", "name", "email", "photo"],
                    //     'specialization_id' => $list->id,
                    // ]
                );
                if (isset($data) && $data->count() > 0) {
                    $data = collect($data->toArray())->map(function ($list) {
                        /** set custom relation with profiles specializations many relation */
                        if (isset($list['specialization_ids'])) {
                            $list['specialization_details'] = $this->specializationsRepository->getDetailsByInput([
                                "ids" => $list['specialization_ids'],
                                "list" => ["id", "name"]
                            ]);
                            return $list;
                        }
                    })->all();
                }

                /** return professional profile data */
                return [
                    "id" => $list->id,
                    "name" => $list->name,
                    "data" => $data
                ];
            });
        }
        $response['professional_user_list'] = $profileResponseGroupWise ?? [];
        return $this->makeResponse($response, __('validation.common.details_found', ['module' => $this->moduleList]));
    }

    public function getProfessionalUserProfileListByInput(Request $request)
    {
        $input = $request->all();

        $professionalUsers = $this->professionalProfileRepository->getDetailsByInput($input);
        $professionalUsers = $professionalUsers->toArray();
        if (count($professionalUsers) == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleList]));
        }
        foreach ($professionalUsers as $key => &$list) {
            if (isset($list['specialization_ids'])) {
                $list['specialization_details'] = $this->specializationsRepository->getDetailsByInput([
                    "ids" => $list['specialization_ids'],
                    "list" => ["id", "name"]
                ]);
            }
        }
        return $this->sendSuccessResponse($professionalUsers, __('validation.common.details_found', ['module' => $this->moduleList]));
    }

    /**
     * eventListingFn => Event Listing
     *
     * @param mixed $input
     *
     * @return void
     */
    public function eventListingFn($input)
    {
        $events = $this->loadCenterEventRepository->getDetails($input);
        if (isset($events) && $events['count'] == 0) {
            return $this->makeError(null, __('validation.common.details_not_found', ['module' => $this->moduleEvent]));
        }
        $currentDate = $this->getCurrentDateUTC();
        /** get all upcoming events */ /** get all upcoming events */
        $list['upcoming_event'] = array_values(collect($events['list'])->where('date_time', '>=', $currentDate)->all());
        /** get all recent events */
        $list['recent_event'] = array_values(collect($events['list'])->where('date_time', '<=', $currentDate)->all());
        return $this->makeResponse($list, __('validation.common.details_found', ['module' => $this->moduleEvent]));
    }

    public function getEventTypeList(Request $request)
    {
        $eventType = $this->eventTypeRepository->getDetailsByInput([
            'is_active' => true
        ]);
        if (!isset($eventType)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Event Types"]));
        }
        return $this->sendSuccessResponse($eventType, __('validation.common.details_found', ['module' => "Event Types"]));
    }

    /**
     * getUsersList => get users details when user searching
     *
     * @param mixed $request
     *
     * @return void
     */
    public function getUsersList(Request $request)
    {
        $input = $request->all();

        /** get all following users */
        $followedUsers = $this->userFollowersRepository->getDetailsByInput([
            'user_id' => $this->userId,
            'first' => true
        ]);
        /** check is user has been followed or not. */
        if (!isset($followedUsers)) return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleUser]));

        $followedUsers = $followedUsers->toArray();
        if (!isset($followedUsers) || isset($followedUsers['following_ids']) == null) return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleUser]));
        $input['ids'] = $followedUsers['following_ids'];
        $users = $this->usersRepositoryEloquent->getDetails($input);
        if (isset($users) && $users['count'] === 0) return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleUser]));
        return $this->sendSuccessResponse($users, __('validation.common.details_found', ['module' => $this->moduleUser]));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        /** set required validation */
        $validation = $this->requiredValidation(['status', 'user_id'], $input);
        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);

        /** create load center event */
        if ($input['status'] === LOAD_CENTER_STATUS_EVENT) {
            $response = $this->createUpdateLoadCenterEventFn($input);
            /** check any error here */
            if (isset($response) && $response['flag'] === false) {
                return $this->sendBadRequest(null, $response['message']);
            }
            return $this->sendSuccessResponse($response['data'], $response['message']);
        } elseif ($input['status'] === LOAD_CENTER_STATUS_REQUEST) {
            /** create load center request */
            $response = $this->createUpdateLoadCenterRequestFn($input);
            /** check any error here */
            if (isset($response) && $response['flag'] === false) {
                return $this->sendBadRequest(null, $response['message']);
            }
            return $this->sendSuccessResponse($response['data'], $response['message']);
        }
        /** if not gone in condition then return this error. */
        return $this->sendBadRequest(null, __("validation.common.invalid_key", ["key" => "status"]));
    }

    /**
     * updateRequest
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function updateRequest(Request $request, $id)
    {
        $input = $request->all();
        /** create load center request */
        $response = $this->createUpdateLoadCenterRequestFn($input, $id);
        /** check any error here */
        if (isset($response) && $response['flag'] === false) {
            return $this->sendBadRequest(null, $response['message']);
        }
        return $this->sendSuccessResponse($response['data'], $response['message']);
    }

    /**
     * createLoadCenterEventFn => create an event
     *
     * @param mixed $input
     *
     * @return array
     */
    public function createUpdateLoadCenterEventFn($input = null, $id = null)
    {
        /** check model validation */
        $validation = LoadCenterEvent::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->makeError(null, $validation->errors()->first());
        }

        // upload image at create time and update time
        if (isset($input['event_image'])) {
            $data = $this->imageController->moveFile($input['event_image'], 'events');
            if (isset($data) && $data['flag'] === false) {
                return $this->makeError(null, $data['message']);
            }
            $input['event_image'] = $data['data']['image'];
        }

        $loadCenterEvent = $this->loadCenterEventRepository->updateOrCreate(['id' => $id], $input);
        return $this->makeResponse(
            $loadCenterEvent,
            !!$id
                ? __("validation.common.updated", ["module" => $this->moduleEvent])
                : __("validation.common.created", ["module" => $this->moduleEvent])
        );

        /** if id found then update it else create new load event */
        // if (!!$id) {
        //     dd('update');
        // } else {

        //     // create event store
        //     $loadCenterEvent = $this->loadCenterEventRepository->create($input);
        //     return $this->makeResponse($loadCenterEvent, __("validation.common.created", ["module" => $this->moduleEvent]));
        // }
    }

    /**
     * createUpdateLoadCenterRequestFn => Create And Update Request
     *
     * @param mixed $input
     * @param mixed $id
     *
     * @return array
     */
    public function createUpdateLoadCenterRequestFn($input, $id = null)
    {
        /** check model validation */
        $validation = LoadCenterRequest::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->makeError(null, $validation->errors()->first());
        }

        if ($id == null) {
            $isAlreadyTwoRequested = $this->loadCenterRequestRepository->getDetailsByInput([
                'user_id' => $input['user_id'] ?? $this->userId,
                'is_count' => true
            ]);
            if (isset($isAlreadyTwoRequested) && $isAlreadyTwoRequested >= 2) {
                return $this->makeError(null, __('validation.common.can_not_create_request_for_two_times_only', ['number' => $isAlreadyTwoRequested]));
            }
        }
        $loadCenterEvent = $this->loadCenterRequestRepository->updateOrCreate(['id' => $id],  $input);
        return $this->makeResponse(
            $loadCenterEvent->fresh(),
            !!$id
                ? __("validation.common.updated", ["module" => $this->moduleRequest])
                : __("validation.common.created", ["module" => $this->moduleRequest])
        );
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function requestShow($id)
    {
        /** get detail by id */
        $loadCenterRequest = $this->loadCenterRequestRepository->getDetailsByInput(
            [
                'id' => $id,
                'relation' => ['user_detail', 'country_detail'],
                'country_detail_list' => ['id', 'name'],
                'first' => true
            ]
        );
        /** check load$loadCenterRequest if null or not */
        if (!!!$loadCenterRequest) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleRequest]));
        }
        return $this->sendSuccessResponse($loadCenterRequest, __('validation.common.details_found', ['module' => $this->moduleRequest]));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function eventShow($id)
    {
        /** get detail by id */
        $loadCenterEvent = $this->loadCenterEventRepository->getDetailsByInput(
            [
                'id' => $id,
                'relation' => ['user_detail' => ['country_detail'], 'currency_detail', 'cancellation_policy_detail'/* , 'event_type_detail' */],
                'user_detail_list' => ['id', 'photo', 'country_id', 'name', 'email'],
                'country_detail' => ['id', 'name', 'code', 'is_active'],
                'currency_detail_list' => ['id', 'name', 'code', 'is_active'],
                // 'event_type_detail_list' => ['id', 'name', 'code', 'is_active'],
                'currency_detail_where' => ['is_active' => true],
                'first' => true
            ]
        );
        if (!!!$loadCenterEvent) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleEvent]));
        }

        $getNearestEvents = $this->loadCenterEventRepository->getNearestArea(
            [
                "latitude" => $loadCenterEvent->latitude,
                'longitude' => $loadCenterEvent->longitude
            ]
        );
        $loadCenterEvent['nearest_events'] = $getNearestEvents ?? [];
        return $this->sendSuccessResponse($loadCenterEvent, __('validation.common.details_found', ['module' => $this->moduleEvent]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $input = $request->all();
        /** set required validation */
        $validation = $this->requiredValidation(['id', 'status', 'user_id'], $input);
        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);


        /** get event details  */
        $loadCenterEvent = $this->loadCenterEventRepository->getDetailsByInput(
            [
                'id' => $input['id'],
                'first' => true
            ]
        );

        /** check event is found or not */
        if (!!!$loadCenterEvent) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleEvent]));
        }

        /** to crete an event */
        if (isset($input['status']) && $input['status'] === LOAD_CENTER_STATUS_EVENT) {
            $response = $this->createUpdateLoadCenterEventFn($input, $input['id']);
            if (isset($response) && $response['flag'] == false) {
                return $this->sendBadRequest(null, $response['message']);
            }

            /** here event was successfully updated  then  remove old image */
            $this->imageController->removeImageFromStorage($loadCenterEvent->event_image);
            return $this->sendSuccessResponse($response['data'], $response['message']);
        }

        return $this->sendBadRequest(null, __("validation.common.invalid_key", ["key" => "status"]));
    }

    /** Destroy Request.
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function destroyRequest($id)
    {
        /** first check request details found or not */
        $requestDetails = $this->loadCenterRequestRepository->getDetailsByInput([
            'user_id' => $this->userId,
            'id' => $id,
            'first' => true
        ]);

        if (!!!isset($requestDetails)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleRequest]));
        }

        /** delete load center request */
        $this->loadCenterRequestRepository->delete($id);
        return $this->sendSuccessResponse(null, __('validation.common.deleted'));
    }
}
