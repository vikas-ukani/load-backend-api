<?php

namespace App\Http\Controllers\API\v1\Auth;

use App\Http\Controllers\API\v1\AllInOneController;
use App\Http\Controllers\API\v1\Setting\SettingController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImageHelperController;
use App\Libraries\Repositories\AccountRepositoryEloquent;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Libraries\Repositories\UsersSnoozeRepositoryEloquent;
use App\Notifications\ChangePasswordNotification;
use App\Notifications\EmailVerificationNotification;
use App\Supports\DateConvertor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use DateConvertor;
    protected $usersRepository;
    protected $imageController;
    protected $usersSnoozeRepository;
    protected $accountRepositoryEloquent;

    public function __construct(
        UsersRepositoryEloquent $usersRepository,
        ImageHelperController $imageHelperController,
        AccountRepositoryEloquent $accountRepositoryEloquent,
        UsersSnoozeRepositoryEloquent $usersSnoozeRepository
    ) {
        $this->usersRepository = $usersRepository;
        $this->imageController = $imageHelperController;
        $this->usersSnoozeRepository = $usersSnoozeRepository;
        $this->accountRepositoryEloquent = $accountRepositoryEloquent;
    }

    /**
     * signUp => Register New User
     *
     * @param mixed $request
     *
     * @return \Illuminate\Http\JsonResponse
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
     * @param mixed $input
     *
     * @return array
     */
    public function createFirstUser($input = null)
    {
        $validation = $this->requiredValidation(['email', 'password', 'confirm_password'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->makeError(null, $validation['message']);
        }
        /**check email manual validation */
        $emailIsExist = $this->usersRepository->getDetailsByInput(
            [
                'email' => $input['email'],
                'first' => true
            ]
        );
        if (isset($emailIsExist) && $emailIsExist->count() > 0) {
            return $this->makeError(null, __('validation.unique', ['attribute' => 'email']));
        }

        /** password confirmations */
        if ($input['password'] != $input['confirm_password']) {
            return $this->makeError(null, __('validation.confirmed', ['attribute' => 'password']));
        }

        /** get FREE account id */
        $account = $this->accountRepositoryEloquent->getDetailsByInput([
            'search' => ACCOUNT_TYPE_FREE,
            'first' => true
        ]);
        /** check if data found then set to default id  */
        if (!!$account) {
            $input['account_id'] = $account->id;
        }
        /** make hashed password */
        $input['password'] = Hash::make($input['password']);

        // FIXME => Remove This line when smtp issue solve on server
        // $input['email_verified_at'] = $this->getCurrentDateUTC();

        /** first create user */
        $user = $this->usersRepository->create($input);
        $user = $user->fresh();

        /** send email to verify account */
        try {
            $user->notify(new EmailVerificationNotification());
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->makeError(null, $exception->getMessage());
        }

        $responseData = [
            'user' => $user,
            'next_step' => REGISTRATION_STEP_COMPLETE_PROFILE
        ];
        return $this->makeResponse($responseData, __('validation.common.created', ['module' => "User"]));
    }

    /**
     * updateUserProfileFn => Update Profile When user signup and step 2
     *
     * @param mixed $input
     *
     * @return array
     */
    public function updateUserProfileFn($input = null)
    {
        /** check profile required validation */
        $validation = $this->requiredValidation(['id', 'name', 'date_of_birth', 'gender', 'height', 'weight', 'photo'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->makeError(null, $validation['message']);
        }

        // FIXME Image uploading not working in live server
        try {
            /** file upload */
            $data = $this->imageController->moveFile($input['photo'], 'users');
            if (isset($data) && $data['flag'] == false) {
                return $this->makeError(null, $data['message']);
            }
            $input['photo'] = $data['data']['image'];
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->makeError(null, $exception->getMessage());
        }

        /** convert date iso to utc format */
        // $input['date_of_birth'] = $this->isoToUTCFormat($input['date_of_birth']); // NOTE No need to convert to UTC format

        /** set to profile complete flag */
        $input['is_profile_complete'] = true;

        /** set user type if not found */
        $input['user_type'] = $input['user_type'] ?? USER_TYPE_USER;

        /** create HR MAX from date of birth in setting training */
        if (isset($input['date_of_birth'])) {
            $this->generateHRMaxFromDateOfBirth($input['date_of_birth'], $input['id']);
        }

        /** update some info of users */
        $user = $this->usersRepository->updateRich($input, $input['id']);
        if (!!!$user) {
            return $this->makeError(null, __('validation.common.invalid_user'));
        }
        $token = Auth::tokenById($user->id);

        $returnResponse = $this->makeAuthTokenResponse($user, $token);
        return $this->makeResponse($returnResponse, __('validation.common.created', ['module' => "User"]));
    }

    public function generateHRMaxFromDateOfBirth($dateOfBirth = null, $userID = null)
    {
        if (isset($dateOfBirth) && isset($userID)) {
            #pass
            $birthArray = explode('-', $dateOfBirth);
            $birthYear = end($birthArray);

            $settingController = app(SettingController::class);
            $hRMax = 206.9 - (0.67 * ($this->getCurrentYear() - (int) $birthYear));
            $settingController->createORUpdateSettingProgram(['hr_max' => $hRMax], $userID);
        }
    }

    /**
     * makeAuthTokenResponse => Make Json Token Response
     *
     * @param mixed $user
     * @param mixed $token
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
     * login for device
     *
     * @param mixed $request
     *
     * @return void
     */
    public function login(Request $request)
    {
        $input = $request->all();
        $credentials = $request->only('email', 'password');

        /** check email or password */
        $responseError = $this->requiredValidation(['email', 'password'], $input);
        if (isset($responseError) && $responseError['flag'] == false) {
            return $this->sendBadRequest(null, $responseError['message']);
        }
        if ($token = JWTAuth::attempt($credentials)) {
            /** UPDATE lat long values */
            app(AllInOneController::class)->updateLatitudeAndLongitude([
                "latitude" => $input['latitude'] ?? null,
                "longitude" => $input['longitude'] ?? null,
                "id" => Auth::id()
            ]);
            $returnDetails = $this->respondWithToken($token);

            /** set snooze details in login response */
            if (isset($returnDetails['user'])) {
                $returnDetails['user']['user_snooze_detail'] = $this->usersSnoozeRepository->getDetailsByInput(
                    [
                        'user_id' => Auth::id(),
                        'first' => true,
                        'list' => ['id', 'user_id', 'start_date', 'end_date']
                    ]
                );
            }

            /** set last login date to current date */
            $this->setLastLoginDatetime();
            return $this->sendSuccessResponse($returnDetails, __('validation.common.login_success'));
        }
        return $this->sendBadRequest(null, __('validation.common.email_password_not_match'), RESPONSE_UNAUTHORIZED_REQUEST);
    }

    /**
     * respondWithToken
     * => return response with token
     * @param mixed $token
     *
     * @return void
     */
    protected function respondWithToken($token)
    {
        return [
            'user' => Auth::user()->fresh(),
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
            $user->last_login_at = $this->getCurrentDateUTC();
            $user->save();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * resetPasswordFn => to send mail
     *
     * @param mixed $request
     *
     * @return void
     */
    public function resetPasswordFn(Request $request)
    {
        $input = $request->all();
        /** check email or password */
        $responseError = $this->requiredValidation(['email'], $input);
        if (isset($responseError) && $responseError['flag'] == false) {
            return $this->sendBadRequest(null, $responseError['message']);
        }

        /** check email is exist or not */
        $user = $this->usersRepository->getDetailsByInput(['email' => $input['email'], 'first' => true]);
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
     * @param mixed $user
     * @return void
     */
    public function makeTokenURL($user)
    {
        //  get user token
        $token = JWTAuth::fromUser($user);
        /** get current url */
        // $url = request()->root();
        $url = url();
        $url .= '/auth/change-password?email=' . $user->email;
        $url .= '&token=' . $token;

        return $url;
    }

    /**
     * changePasswordFn => user change password
     *
     * @param mixed $request
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
