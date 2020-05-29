<?php

namespace App\Http\Controllers\Admin\Users;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Http\Controllers\ImageHelperController;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    protected $moduleName = "User";

    protected $userRepository;
    protected $imageController;

    public function __construct(
        UsersRepositoryEloquent $userRepository,
        ImageHelperController $imageController
    ) {
        $this->imageController = $imageController;
        $this->userRepository = $userRepository;
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

        $users = $this->userRepository->getDetails($input);
        if (isset($users) && $users['count'] === 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($users, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * profileUpdate => admin profile update
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function profileUpdate(Request $request)
    {
        $input = $request->all();

        /** get old user details  */
        $oldUser = $this->userRepository->find(Auth::id());

        # update profile
        if (isset($input['photo'])) {
            /** file upload */
            $data = $this->imageController->moveFile($input['photo'], 'users');
            if (isset($data) && $data['flag'] === false) {
                return $this->makeError(null, $data['message']);
            }
            $input['photo'] = $data['data']['image'];
        }

        /** update some info of users */
        $user = $this->userRepository->updateRich($input, Auth::id());
        $token = Auth::tokenById($user->id);

        /** check for photo is exist then remove  old image */
        if (isset($input['photo'])) {
            /** remove old image whe data was updated */
            $this->imageController->removeImageFromStorage($oldUser->photo); // use old image path here
        }

        $returnResponse = $this->makeAuthTokenResponse($user, $token);
        return $this->sendSuccessResponse($returnResponse, __('validation.common.updated', ['module' => "User"]));
    }

    /**
     * makeAuthTokenResponse => Make Json Token Response
     *
     * @param  mixed $user
     * @param  mixed $token
     *
     * @return void
     */
    public function makeAuthTokenResponse($user, $token)
    {
        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            // 'user'=> $this->guard()->user()
            // 'expires_in' => $this->guard()->factory()->getTTL() * 60
        ];
    }

    /**
     * update => Update Users Data
     *
     * @param  mixed $request
     * @param  mixed $id
     *
     * @return void
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        /** check model validation */
        $validation = User::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        /** get event details  */
        $user = $this->userRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );

        /**  check event is found or not */
        if (!!!$user) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        /** upload image if user want to update profile here */
        if (isset($input['photo'])) {
            $data = $this->imageController->moveFile($input['photo'], 'users');
            if (isset($data) && $data['flag'] === false) {
                return $this->sendBadRequest(null, $data['message']);
            }
            $input['photo'] = $data['data']['image'];
        }

        $updatedUser = $this->userRepository->updateRich($input, $id);

        /** check for photo is exist then remove  old image */
        if (isset($input['photo'])) {
            /** remove old image whe data was updated */
            $this->imageController->removeImageFromStorage($user->photo); // use old image path here
        }
        return $this->sendSuccessResponse(['user' => $updatedUser], __("validation.common.updated", ["module" => $this->moduleName]));
    }

    /**
     * show => get user details by id
     *
     * @param  mixed $id
     *
     * @return void
     */
    public function show($id)
    {
        /** get detail by id */
        $user = $this->userRepository->getDetailsByInput(
            [
                'id' => $id,
                'relation' => ['country_detail', 'account_detail', 'user_snooze_detail'], // 'user_detail' => ['country_detail']
                'country_detail_list' => ['id', 'name'],
                'account_detail_list' => ['id', 'name', 'free_trial_days'],
                'user_snooze_detail_list' => ['id', 'user_id', 'start_date', 'end_date'],
                'first' => true
            ]
        );
        /** check load$user if null or not */
        if (!!!$user) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        return $this->sendSuccessResponse($user->fresh(), __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * statusChange => update status to active or deactivate
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function statusChange(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['id'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        $actionForce = $this->userRepository->updateRich($input, $input['id']);
        return $this->sendSuccessResponse($actionForce->fresh(), __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
