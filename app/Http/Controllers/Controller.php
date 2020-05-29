<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Libraries\Repositories\UsersRepositoryEloquent;

class Controller extends AppController
{
    protected $userRepository;

    public function __construct(UsersRepositoryEloquent $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    /**
     * userEmailVerify => when user register then send email to active email
     *
     * @param  mixed $id
     *
     * @return void
     */
    public function userEmailVerify($id = null)
    {
        $user = $this->userRepository->getDetailsByInput(['id' => $id, 'first' => true]);
        if (!isset($user)) {
            abort(404);
        }

        /** check verify for first time */
        if ($user->email_verified_at == null) {
            $user->email_verified_at = Carbon::now(env('APP_TIMEZONE', 'UTC'));
            $user->save();
            $successMessage =  __('validation.common.successfully_activated_account');
            echo "<script> alert('$successMessage'); </script>";
        } else {
            $errorMessage =  __('validation.common.already_account_activated');
            echo "<script> alert( '$errorMessage' );  </script>";
        }
        echo "<script>window.close();</script>";
    }
}
