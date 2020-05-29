<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Supports\DateConvertor;
use App\Notifications\NoLogInForTwoWeeks;
use Illuminate\Support\Facades\Notification;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Libraries\Repositories\AccountRepositoryEloquent;

class LoginRemainderForTwoWeek extends Command
{
    use DateConvertor;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:remind-to-login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command will notify to user who has no longer login in two weeks.';
    protected $usersRepository;
    protected $accountRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(UsersRepositoryEloquent $usersRepository, AccountRepositoryEloquent $accountRepository)
    {
        parent::__construct();
        $this->usersRepository = $usersRepository;
        $this->accountRepository = $accountRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $currentDate = $this->getCurrentDateUTC();
        $previousDate = $this->subtractWeeksFromCurrentUTC(2);

        /** get details for account id */
        $professionalAccount =  $this->accountRepository->getDetailsByInput([
            'is_active' => true,
            'code' => ACCOUNT_TYPE_PROFESSIONAL,
            'first' => true
        ]);

        /** if account details found then send notification */
        if (isset($professionalAccount) && isset($professionalAccount->id)) {
            /** get users to send notification send to remain two week not login */
            $users = $this->usersRepository->getDetailsByInput([
                'last_login_at' => $previousDate, // check last login date 
                'account_id' => $professionalAccount->id, // check for account is Professional 
                'is_last_login' => false, // check for null last login date
                'is_active' => true, // check for user is active
                'is_profile_complete' => true, // check for user complete their profile 
                'user_type' => USER_TYPE_USER // check to send user only
            ]);

            /** send notification here */
            Notification::send($users, new NoLogInForTwoWeeks());
            // set command message
            $this->info("Email Was send.");
        } else {
            $this->error("Account Details not found.");
        }
    }
}
