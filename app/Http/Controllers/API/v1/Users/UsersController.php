<?php

namespace App\Http\Controllers\API\v1\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ImageHelperController;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    protected $moduleName = "User";

    protected $userRepository;
    protected $imageController;

    public function __construct(
        UsersRepositoryEloquent $userRepository,
        ImageHelperController $imageController
    )
    {
        $this->imageController = $imageController;
        $this->userRepository = $userRepository;
    }

    /** Get All user listing
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $input = $request->all();
        if (isset($input['user_id'])) unset($input['user_id']);

        try {
            $users = $this->userRepository->getDetails($input);
            return $this->sendSuccessResponse($users, __('validation.common.details_found', ['module' => $this->moduleName]));
        } catch (\Exception $exception) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }
    }

    /** Get user details using user id
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** get event details  */
        $user = $this->userRepository->getDetailsByInput(
            [
                'relation' => ['country_detail', 'account_detail', 'user_snooze_detail'],
                'country_detail_list' => ['id', 'name', 'country_code'],
                'account_detail_list' => ['id', 'name'],
                'user_snooze_detail_list' => ['id', 'user_id', 'start_date', 'end_date'],
                'id' => $id,
                'first' => true
            ]
        );
        /**  check event is found or not */
        if (!$user) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
        }

        return $this->sendSuccessResponse($user, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * update => Update Users Data
     *
     * @param mixed $request
     * @param mixed $id
     *
     * @return void|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        /** custom validation applied */
        $validation = $this->requiredValidation(['date_of_birth', 'country_id', 'country_code', 'mobile', 'email'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        /** check model validation */
        // $validation = User::validation($input, $id);
        // if (isset($validation) && $validation->errors()->count() > 0) {
        //     return $this->sendBadRequest(null, $validation->errors()->first());
        // }

        /** get event details  */
        $user = $this->userRepository->getDetailsByInput(
            [
                'id' => $id,
                'first' => true
            ]
        );

        /**  check event is found or not */
        if (!$user) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));
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

        /** get relation wise data in profile */
        $updatedUser = $this->userRepository->getDetailsByInput(
            [
                'relation' => ['country_detail', 'account_detail', 'user_snooze_detail'],
                'country_detail_list' => ['id', 'name', 'country_code'],
                'account_detail_list' => ['id', 'name'],
                'user_snooze_detail_list' => ['id', 'user_id', 'start_date', 'end_date'],
                'id' => $updatedUser->id,
                'first' => true
            ]
        );

        // return $this->sendSuccessResponse(['user' => $updatedUser], __("validation.common.updated", ["module" => $this->moduleName]));
        return $this->sendSuccessResponse($updatedUser, __("validation.common.updated", ["module" => $this->moduleName]));
    }
}
