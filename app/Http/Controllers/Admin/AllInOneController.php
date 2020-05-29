<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\AccountRepositoryEloquent;

class AllInOneController extends Controller
{
    protected $accountRepository;

    public function __construct(AccountRepositoryEloquent  $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    //



    public function getUserCountsByUserType()
    {
        $counts = $this->accountRepository->getDetailsByInput([
            'relation' => [
                'user_details'
            ],
            'user_details_list' => ['id', 'account_id'],
            'list' => ['id', 'code']
        ]);
        $response['free_user_counts'] = collect($counts)->where('code', ACCOUNT_TYPE_FREE)->pluck('user_details')->first()->count();
        $response['premium_user_counts'] = collect($counts)->where('code', ACCOUNT_TYPE_PREMIUM)->pluck('user_details')->first()->count();
        $response['professional_user_counts'] = collect($counts)->where('code', ACCOUNT_TYPE_PROFESSIONAL)->pluck('user_details')->first()->count();
        return $this->sendSuccessResponse($response, __('validation.common.details_found', ['module' => "User counts"]));
    }
}
