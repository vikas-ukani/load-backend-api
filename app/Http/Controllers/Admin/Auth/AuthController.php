<?php

namespace App\Http\Controllers\Admin\Auth;

use Illuminate\Http\Request;
use App\Supports\DateConvertor;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImageHelperController;
use App\Notifications\ChangePasswordNotification;
use App\Notifications\EmailVerificationNotification;
use App\Libraries\Repositories\UsersRepositoryEloquent;

class AuthController extends Controller
{
    use DateConvertor;
    protected $usersRepository;

    public function __construct(UsersRepositoryEloquent  $usersRepository, ImageHelperController $imageHelperController)
    {
        $this->usersRepository = $usersRepository;
        $this->imageController = $imageHelperController;
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
     * signUp => Register New User
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function signUp(Request $request)
    {
        $input = $request->all();
        /** make require validation from input */
        $validation = $this->requiredValidation(['step'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        /** check step for register  */
        if ($input['step'] === REGISTRATION_STEP_SIGNUP) {
            $data = $this->createFirstUser($input);
            if (isset($data) && $data['flag'] === false) {
                return $this->sendBadRequest(null, $data['message']);
            }
            return $this->sendSuccessResponse($data['data'], $data['message']);
        } elseif ($input['step'] === REGISTRATION_STEP_COMPLETE_PROFILE) {
            $data = $this->updateUserProfileFn($input);
            if (isset($data) && $data['flag'] === false) {
                return $this->sendBadRequest(null, $data['message']);
            }
            return $this->sendSuccessResponse($data['data'], $data['message']);
        }
    }

    /**
     * createFirstUser => Register Step wise.
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function createFirstUser($input = null)
    {
        $validation = $this->requiredValidation(['email', 'password', 'confirm_password'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->makeError(null, $validation['message']);
        }
        /**check email manual validation */
        $emailIsExist = $this->usersRepository->getDetailsByInput(['email' => $input['email'], 'first' => true]);
        if (isset($emailIsExist) && $emailIsExist->count() > 0) {
            return $this->makeError(null, __('validation.unique', ['attribute' => 'email']));
        }

        /** password confirmations */
        if ($input['password'] != $input['confirm_password']) {
            return $this->makeError(null, __('validation.confirmed', ['attribute' => 'password']));
        }
        // make hashed password
        $input['password'] = Hash::make($input['password']);

        /** first create user */
        $user = $this->usersRepository->create($input);
        $user = $user->fresh();

        // FIXME Not Working In Digital SErver
        // try {
        //     $user->notify(new EmailVerificationNotification());
        // } catch (\Exception $exception) {
        //     \Log::error($exception->getMessage());
        // }

        $responseData = [
            'user' => $user,
            'next_step' => REGISTRATION_STEP_COMPLETE_PROFILE
        ];
        return $this->makeResponse($responseData, __('validation.common.created', ['module' => "User"]));
    }

    /**
     * updateUserProfileFn => Update Profile When user signup and step 2
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function updateUserProfileFn($input = null)
    {
        /** check profile required validation */
        $validation = $this->requiredValidation(['id', 'name', 'date_of_birth', 'gender', 'height', 'weight', 'photo'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->makeError(null, $validation['message']);
        }

        /** file upload */
        $data =  $this->imageController->moveFile($input['photo'], 'users');
        if (isset($data) && $data['flag'] === false) {
            return $this->makeError(null, $data['message']);
        }
        $input['photo'] = $data['data']['image'];

        /** convert date iso to utc format */
        // $input['date_of_birth'] = $this->isoToUTCFormat($input['date_of_birth']);  // NOTE No need to convert to UTC format

        /** set to profile complete flag */
        $input['is_profile_complete'] = true;

        /** update some info of users */
        $user = $this->usersRepository->updateRich($input, $input['id']);
        $token = Auth::tokenById($user->id);

        $returnResponse = $this->makeAuthTokenResponse($user, $token);
        return $this->makeResponse($returnResponse, __('validation.common.created', ['module' => "User"]));
    }

    /**
     * login for device
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function login(Request $request)
    {
        $input = $request->all();
        $credentials = $request->only('email', 'password');

        /** check email or password */
        $responseError = $this->requiredValidation(['email', 'password'], $input);
        if (isset($responseError) && $responseError['flag'] === false) {
            return $this->sendBadRequest(null, $responseError['message']);
        }
        if ($token = JWTAuth::attempt($credentials)) {
            $returnDetails =  $this->respondWithToken($token);

            /** set last login date to current date */
            $this->setLastLoginDatetime();
            return $this->sendSuccessResponse($returnDetails, __('validation.common.login_success'));
        }
        return $this->sendBadRequest(null, __('validation.common.email_password_not_match'), RESPONSE_UNAUTHORIZED_REQUEST);
    }
    protected function respondWithToken($token)
    {
        return [
            'user' => Auth::user(),
            'access_token' => $token,
            'token_type' => 'Bearer',
            // 'expires_in' => $this->guard()->factory()->getTTL() * 60
        ];
    }

    /**
     * setLastLoginDatetime => set Last login Date Time
     *
     * @return void
     */
    public function setLastLoginDatetime()
    {
        try {
            $user = Auth::user();
            /** to check for from last 2 ween user didn't logged in => checking on cron commands */
            $user->last_login_at = $this->getCurrentDateUTC();
            $user->save();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * resetPasswordFn => for reset password send mail to email and generate new token
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function forgotPassword(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['email'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        /** check email is exist or not */
        $user = $this->usersRepository->getDetailsByInput(['email' => $input['email'], 'user_type' => USER_TYPE_ADMIN, 'first' => true]);
        if (!isset($user)) {
            return $this->sendBadRequest(null, __('validation.common.email_not_exist', ['key' => 'email']));
        }

        $url = $this->makeTokenURL($user);
        try {
            // send notification email
            $user->notify(new ChangePasswordNotification($user, $url));
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->sendSuccessResponse(null, $exception->getMessage());
        }

        return $this->sendSuccessResponse(null, __('validation.common.forgot_password_email_send'));
    }

    /**
     * makeTokenURL => Create Url to send Mail Button
     * @param  mixed $user
     * @return void
     */
    public function makeTokenURL($user)
    {
        //  get user token
        $token = JWTAuth::fromUser($user);
        /** get current url */
        // $url = request()->root();
        $url = url();
        // $url .= '/admin/change-password?email=' . $user->email;
        // $url .= '&token=' . $token;
        $url .= '/admin#/change-password/' . $user->email;
        $url .= '/' . $token;

        return $url;
    }


    /**
     * changePasswordFn => user change password
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function changePasswordFn(Request $request)
    {
        $input = $request->all();
        /** required validation */
        $validation = $this->requiredValidation(['email', 'password', 'confirm_password'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }
        /** password confirmations */
        if ($input['password'] != $input['confirm_password']) {
            return $this->makeError(null, __('validation.confirmed', ['attribute' => 'password']));
        }

        /** make hash password */
        $input['password'] = Hash::make($input['password']);

        $token = null;
        if ($request->header('Authorization')) {
            $key = explode(' ', $request->header('Authorization'));
            $token = $key[1];
        }
        if (!isset($token)) {
            return $this->sendBadRequest(null, __('validation.common.token_required_in_header'));
        }

        /** password change process */
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!isset($user)) {
                return $this->sendBadRequest(null, __('validation.common.token_invalid'));
            }

            /** save user password */
            $user->password = $input['password'];
            $user->save();
            JWTAuth::setToken($token)->invalidate();

            return $this->sendSuccessResponse(null, __('validation.common.password_changed_success'));
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->sendBadRequest(null, __('validation.common.token_required_in_header'));
        }
    }
}
