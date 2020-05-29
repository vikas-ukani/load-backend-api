<?php

namespace App\Http\Controllers\API\v1\Setting;

use App\Models\UsersSnooze;
use App\Supports\DateConvertor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Libraries\Repositories\AccountRepositoryEloquent;
use App\Libraries\Repositories\UsersSnoozeRepositoryEloquent;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    use DateConvertor;

    protected $usersRepository;
    protected $accountRepository;
    protected $usersSnoozeRepository;

    public function __construct(
        UsersRepositoryEloquent  $usersRepository,
        AccountRepositoryEloquent $accountRepository,
        UsersSnoozeRepositoryEloquent $usersSnoozeRepository
    ) {
        $this->usersRepository = $usersRepository;
        $this->accountRepository = $accountRepository;
        $this->usersSnoozeRepository = $usersSnoozeRepository;
    }


    /**
     * updateAccount => update account type from setting in account tab
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateAccountType(Request $request)
    {
        $input = $request->all();

        /** check required validation */
        $validation = $this->requiredValidation(['account_id'], $input);
        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);

        /** check for account is active or not */
        $account = $this->accountRepository->getDetailsByInput([
            'id' => $input['account_id'],
            'is_active' => true,
            'first' => true
        ]);
        if (!$account) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'account']));
        }

        ### NOTE Upgrade account type and purchase account validity.

        $usersDetail = $this->usersRepository->updateRich([
            'account_id' => $input['account_id']
        ], Auth::id());


        return $this->sendSuccessResponse(null, __('validation.common.updated', ['module' => "Account"]));
    }

    /**
     * updateAccountSnooze => to update an account is snooze or not
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateAccountSnooze(Request $request)
    {
        $input = $request->all();
        # 0.5 check for is_snooze flag validation
        $validation = $this->requiredValidation(['is_snooze'], $input);
        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);

        /** store login user id  */
        $userId = Auth::id();
        $loginUser = Auth::user();
        $snoozeDetails = null;
        # 1 check for validation for start date  and end date if is_snooze is TRUE
        if ($input['is_snooze'] == true) {
            $validation = UsersSnooze::validation($input);
            if (isset($validation) && $validation->errors()->count() > 0) {
                return $this->sendBadRequest(null, $validation->errors()->first());
            }

            # 2 convert date and times
            if (isset($input['start_date'])) {
                $input['start_date'] = $this->isoToUTCFormat($input['start_date']);
            }
            if (isset($input['end_date'])) {
                $input['end_date'] = $this->isoToUTCFormat($input['end_date']);
            }
            $input['user_id'] = $userId;
            // unset($input['is_snooze']);

            # 2.1 check for snooze detail already exits to not
            $snoozeDetails = $this->usersSnoozeRepository->getDetailsByInput([
                'user_id' => $userId,
                'first' => true
            ]);
            # 2.2 if already exist then update else  create new
            if (isset($snoozeDetails)) {
                # Update
                $snoozeDetails->start_date = $input['start_date'];
                $snoozeDetails->end_date = $input['end_date'];
                $snoozeDetails->save();
            } else {
                # Create
                $snoozeDetails = $this->usersSnoozeRepository->create($input);
                $loginUser->is_snooze = $input['is_snooze'];
                $loginUser->save();
            }
            // $this->usersSnoozeRepository->updateOrCreate()
        } else {
            # 4 if FALSE then remove entry from table
            $this->usersSnoozeRepository->deleteWhere([
                'user_id' => $userId
            ]);
            $loginUser->is_snooze = $input['is_snooze'];
            $loginUser->save();
        }
        return $this->sendSuccessResponse($snoozeDetails, __('validation.common.updated', ['module' => "Snooze details"]));
    }
}
