<?php

namespace App\Http\Middleware;

use Closure;
use App\Supports\MessageClass;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Repositories\AccountRepositoryEloquent;

class CheckUserAccount
{
    use MessageClass;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        /**
         * check before access api to check login user account type 
         */

        /** to check user account is professional or premium, and account is active only*/
        $accountRepository = app(AccountRepositoryEloquent::class);
        $getAccountDetails = $accountRepository->getDetailsByInput(['codes' => [ACCOUNT_TYPE_PREMIUM, ACCOUNT_TYPE_PROFESSIONAL], 'is_active' => true, 'list' => 'id']);
        if (isset($getAccountDetails) && count($getAccountDetails) == 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Account"]));
        }
        $accountIds = $getAccountDetails->pluck('id')->toArray();
        // check account id not exist in ids array
        if (!in_array($user->account_id, $accountIds)) {
            return $this->sendBadRequest(null, __('validation.common.please_upgrade_your_account_to', ["account" => "Premium"]));
        }

        $response = $next($request);

        // Post-Middleware Action

        return $response;
    }
}
