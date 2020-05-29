<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\Users;

use App\Http\Controllers\Controller;
use App\Libraries\Repositories\UserFollowersRepositoryEloquent;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserFollowsController extends Controller
{
    protected $userModuleName = "User";

    protected $userRepository;
    protected $userFollowersRepository;

    public function __construct(UsersRepositoryEloquent $userRepository, UserFollowersRepositoryEloquent $userFollowersRepository)
    {
        $this->userRepository = $userRepository;
        $this->userFollowersRepository = $userFollowersRepository;
    }

    /**
     * followUnfollowUser => to Follow and unfollow the users
     *
     * @param mixed $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function followUnfollowUser(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['is_follow'], $input);
        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);

        /** if found multiple user ids then remove single user id come from middleware. */
        if (isset($input['user_ids']) && count($input['user_ids']) > 0) {
            unset($input['user_id']);
        }

        $followingDetails = $this->userFollowersRepository->getDetailsByInput([
            'user_id' => Auth::id(),
            'first' => true
        ]);

        /** set to follow user */
        if (isset($input['is_follow']) && $input['is_follow'] == true) {
            // pass
            $response = $this->followUser($input, $followingDetails);
            if (isset($response) && $response['flag'] == false) {
                return $this->sendBadRequest(null, $response['message']);
            }

            return $this->sendSuccessResponse($response['data'], $response['message']);
        } else if (isset($input['is_follow']) && $input['is_follow'] == false) {
            /** set to unfilled users */
            $response = $this->unfollowUser($input, $followingDetails);
            if (isset($response) && $response['flag'] == false) {
                return $this->sendBadRequest(null, $response['message']);
            }
            return $this->sendSuccessResponse($response['data'], $response['message']);
        }
        dd('Closed');
    }


    /**
     * followUser => to follow the users
     *
     * @param mixed $input
     *
     * @return void
     */
    public function followUser($input, $followingDetails)
    {
        /** check details fount if not then create new records for follows */
        if ($followingDetails == null) {
            # CREATE
            $createData = [
                'user_id' => \Auth::id(),
                'following_ids' => [$input['user_id']]
            ];
            $returnData = $this->userFollowersRepository->create($createData);
        } else {
            $followingDetails = $followingDetails->toArray();

            /** check if already exists in db */
            if (isset($followingDetails['following_ids']) && !in_array($input['user_id'], $followingDetails['following_ids'])) {
                array_push($followingDetails['following_ids'], $input['user_id']);
            } else if ($followingDetails['following_ids'] == null) {
                /** check if records found but following id is null  */
                $followingDetails['following_ids'] = [$input['user_id']];
            }
            $returnData = $this->userFollowersRepository->updateRich($followingDetails, $followingDetails['id']);
        }
        /** return common response for both create or update */
        return $this->makeResponse($returnData, __('validation.common.successfully_followed'));
    }

    /**
     * unfollowUser => to unfollow the users
     *
     * @param mixed $input
     *
     * @return void
     */
    public function unfollowUser($input, $followingDetails)
    {
        if ($followingDetails == null) {
            return $this->makeError(null, __('validation.common.details_found', ['module' => $this->userModuleName]));
        }

        $followingDetails = $followingDetails->toArray();

        if (isset($followingDetails['following_ids'])) {
            if (isset($input['user_id'])) {
                if (($key = array_search($input['user_id'], $followingDetails['following_ids'])) !== false) {
                    unset($followingDetails['following_ids'][$key]);
                }

                //          if (isset($input['user_id']) && count($input['user_id']) > 0) {
                //            if (isset($input['user_ids']) && count($input['user_ids']) > 0) {
                //                foreach ($input['user_ids'] as $userID) {
                //                    if (isset($userID)) {
                /** remove id from current following users */
                //                        if (($key = array_search($userID,  $followingDetails['following_ids'])) !== false) {
                //                            unset($followingDetails['following_ids'][$key]);
                //                        }
                //                    }
                //                }
            }
            if (isset($input['user_ids']) && count($input['user_ids']) > 0) {
                $followingDetails['following_ids'] = array_diff($followingDetails['following_ids'], $input['user_ids']);
             }
            // if (($key = array_search($input['user_id'],  $followingDetails['following_ids'])) !== false) {
            //     unset($followingDetails['following_ids'][$key]);
            // }
        } else {
            // Remove if following ids not found then remove records
            $this->userFollowersRepository->delete($followingDetails['id']);
        }

        # UPDATE
        $returnData = $this->userFollowersRepository->update($followingDetails, $followingDetails['id']);
        return $this->makeResponse($returnData, __('validation.common.successfully_unfollowed'));
    }
}
