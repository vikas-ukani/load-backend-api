<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\LoadCenter;

use App\Http\Controllers\Controller;
use App\Libraries\Repositories\FeedCommentsRepositoryEloquent;
use App\Libraries\Repositories\FeedLikesRepositoryEloquent;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FeedLikeCommentController extends Controller
{
    protected $feedLikesRepository;
    protected $feedCommentsRepository;
    protected $usersRepositoryEloquent;

    public function __construct(
        FeedLikesRepositoryEloquent $feedLikesRepository,
        FeedCommentsRepositoryEloquent $feedCommentsRepository,
        UsersRepositoryEloquent $usersRepositoryEloquent
    ) {
        $this->feedLikesRepository = $feedLikesRepository;
        $this->feedCommentsRepository = $feedCommentsRepository;
        $this->usersRepositoryEloquent = $usersRepositoryEloquent;
    }

    /**
     * likeFeed => like this feed
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function likeFeed(Request  $request)
    {
        $input = $request->all();
        /** check for required validation */
        $validation = $this->requiredValidation(['feed_id'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        /** check for also liked or not */
        $details = $this->feedLikesRepository->getDetailsByInput([
            'feed_id' => $input['feed_id'],
            'first' => true
        ]);

        /** check for id exists in table */
        if ($details == null) {
            # CREATE
            $details = $this->feedLikesRepository->create([
                'feed_id' => $input['feed_id'],
                'user_ids' => [Auth::id()]
            ]);
        } else {
            /** check in users id  Current id is exists or not */
            if (isset($details->user_ids) && in_array(Auth::id(), $details->user_ids)) {
                $details = $details->toArray();
                if (($key = array_search(Auth::id(), $details['user_ids'])) !== false) {
                    unset($details['user_ids'][$key]);
                    /** if user id found then update else delete records, */
                    if (isset($details['user_ids']) && count($details['user_ids']) > 0) {
                        $details = $this->feedLikesRepository->updateRich($details, $details['id']);
                    } else {
                        $details = $this->feedLikesRepository->delete($details['id']);
                    }
                } else {
                    Log::error('In ${__FILE__} on ${__LINE__} got error.');
                }
            } else {
                $details = $this->addCurrentUserIdAndSave($details);
            }
        }

        /** get fresh object */
        if (isset($details)) {
            $details->fresh();
            $details['is_liked'] =  false;
            $details = $details->toArray();
            if (isset($details['user_ids']))
                $details['is_liked'] = (in_array(Auth::id(), $details['user_ids'])) ?  true : false;
        }
        return $this->sendSuccessResponse($details, __('validation.common.feed_liked'));
    }

    /**
     * addCurrentUserIdAndSave => add current user id in liked ids array
     *
     * @param  mixed $detailsObject
     *
     * @return void
     */
    public function addCurrentUserIdAndSave($detailsObject)
    {
        $detailsObject = $detailsObject->toArray();
        if ($detailsObject['user_ids'] == null) $detailsObject['user_ids'] = [];
        array_push($detailsObject['user_ids'], Auth::id());
        return $this->feedLikesRepository->updateRich($detailsObject, $detailsObject['id']);
    }

    /**
     * createComment => store comment in dbs
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function createComment(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['user_id', 'feed_id', 'comment'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        #create
        $commentData = $this->feedCommentsRepository->create($input);
        $commentData->fresh();
        $commentData = $commentData->toArray();
        $commentData['user_detail'] = $this->usersRepositoryEloquent->getDetailsByInput([
            'id' => $commentData['user_id'],
            'list' => ['id', 'name', 'photo'],
            'first' => true,
        ]);
        return $this->sendSuccessResponse($commentData, __('validation.common.saved', ['module' => 'Comment']));
    }

    /**
     * commentList => LISTING OF COMMENT WITH PAGINATION
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function commentList(Request $request)
    {
        $input = $request->all();
        # GET LIST
        $commentList = $this->feedCommentsRepository->getDetails($input);
        $commentList['count']  = $this->feedCommentsRepository->getCommentCount($input['feed_id']);

        $likesDetails = $this->feedLikesRepository->getDetailsByInput($input);

        /** get training likes details */
        $likesDetails  = $this->getLikesDetails($likesDetails);
        $response = [
            'comment_details' => $commentList,
            'like_details' => array_first($likesDetails)
        ];
        return $this->sendSuccessResponse($response, __('validation.common.details_found', ['module' => "Comments"]));
    }

    /**
     * getLikesDetails => get Likes details by feed
     *
     * @param  mixed $list
     *
     * @return void
     */
    public function getLikesDetails($response)
    {
        $response = collect($response->toArray())->map(function ($list) {
            /** check for user ids  */
            if (isset($list) && isset($list) && $list['user_ids']) {
                $list['is_liked'] = false;
                if (in_array(Auth::id(), (array) $list['user_ids'])) $list['is_liked'] = true;
                $lastFiveIds = array_slice((array) $list['user_ids'], -5);
                /** check for last five ids */
                if (isset($lastFiveIds)) {
                    $list['images'] = collect($lastFiveIds)->map(function ($id) {
                        return $this->usersRepositoryEloquent->getDetailsByInput([
                            'id' => $id,
                            'list' => ["id", "photo"],
                            'first' => true
                        ]);
                    });
                }
            }
            return $list;
        });
        return $response;
    }
}
