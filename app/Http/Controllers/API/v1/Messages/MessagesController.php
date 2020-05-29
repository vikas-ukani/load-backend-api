<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\Messages;

use App\Http\Controllers\Controller;
use App\Libraries\Repositories\BodyPartRepositoryEloquent;
use App\Libraries\Repositories\LibraryRepositoryEloquent;
use App\Libraries\Repositories\MessageConversationRepositoryEloquent;
use App\Libraries\Repositories\UserFollowersRepositoryEloquent;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagesController extends Controller
{
    protected $moduleName = "Message conversation";

    protected $usersRepository;
    protected $libraryRepository;
    protected $bodyPartRepository;
    protected $userFollowersRepository;
    protected $messageConversationRepository;

    public function __construct(
        UsersRepositoryEloquent $usersRepository,
        LibraryRepositoryEloquent $libraryRepository,
        BodyPartRepositoryEloquent $bodyPartRepository,
        UserFollowersRepositoryEloquent $userFollowersRepository,
        MessageConversationRepositoryEloquent $messageConversationRepository
    ) {
        $this->usersRepository = $usersRepository;
        $this->libraryRepository = $libraryRepository;
        $this->bodyPartRepository = $bodyPartRepository;
        $this->userFollowersRepository = $userFollowersRepository;
        $this->messageConversationRepository = $messageConversationRepository;
    }

    /**
     * getUsersList => get users details
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
            'user_id' => Auth::id(),
            'first' => true
        ]);
        if (!isset($followedUsers)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Users"]));
        }

        $followedUsers = $followedUsers->toArray();
        if (!isset($followedUsers) || isset($followedUsers['following_ids']) == null) return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Users"]));
        $input['ids'] = $followedUsers['following_ids'];
        if (isset($input['user_id'])) unset($input['user_id']);
        $users = $this->usersRepository->getDetails($input);
        if (isset($users) && $users['count'] === 0) return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => "Users"]));
        return $this->sendSuccessResponse($users, __('validation.common.details_found', ['module' => "Users"]));
    }


    public function getConversationDetailCustomize(Request $request)
    {
        $input = $request->all();

        $validation = $this->requiredAllKeysValidation(['from_id', 'to_id'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        $conversationDetail = $this->messageConversationRepository->getDetailsByFromOrTo([
            'relation' => ['from_user', 'to_user'],
            'from_user_list' => ['id', 'name', 'photo'],
            'to_user_list' => ['id', 'name', 'photo'],
            'list' => ['id', 'booked_client_id', 'unread_count', 'last_message', 'training_log_id', 'event_id', 'type'],
            'from_id' => $input['from_id'],
            'to_id' => $input['to_id'],
        ]);

        /** check if conversation not exist then create new, */
        if (!!!isset($conversationDetail)) {
            $createConversation = [
                'from_id' => $input['from_id'],
                'to_id' => $input['to_id'],
                'type' => 0
            ];
            $createConversation = $this->messageConversationRepository->create($createConversation);
            $conversationDetail = $this->messageConversationRepository->getDetailsByFromOrTo([
                'relation' => ['from_user', 'to_user'],
                'from_user_list' => ['id', 'name', 'photo'],
                'to_user_list' => ['id', 'name', 'photo'],
                'list' => ['id', 'booked_client_id', 'unread_count', 'last_message', 'training_log_id', 'event_id', 'type'],
                'from_id' => $input['from_id'],
                'to_id' => $input['to_id'],
            ]);
            //            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        $conversationDetail = $conversationDetail->toArray();
        //        dd("final data", $input, $conversationDetail);
        //        if (isset($conversationDetail) && isset($conversationDetail['from_user'])) {
        $conversationDetail['from_id'] = $conversationDetail['from_user']['id'] ?? null;
        $conversationDetail['from_name'] = $conversationDetail['from_user']['name'] ?? null;
        $conversationDetail['from_photo'] = $conversationDetail['from_user']['photo'] ?? null;
        //            from_user_id
        //            from_id
        //            from_name
        //            from_photo
        //        }
        //        if (isset($conversationDetail) && isset($conversationDetail['to_user'])) {
        $conversationDetail['to_id'] = $conversationDetail['to_user']['id'] ?? null;
        $conversationDetail['to_name'] = $conversationDetail['to_user']['name'] ?? null;
        $conversationDetail['to_photo'] = $conversationDetail['to_user']['photo'] ?? null;
        //            to_user_id
        //            to_id
        //            to_name
        //            to_photo
        //        }
        unset($conversationDetail['from_user'], $conversationDetail['to_user']);
        return $this->sendSuccessResponse($conversationDetail, __('validation.common.details_found', ['module' => $this->moduleName]));
    }
}
