<?php

namespace App\Console\Commands;

use App\Libraries\Repositories\UsersRepositoryEloquent;
use App\Libraries\Repositories\UsersSnoozeRepositoryEloquent;
use App\Supports\DateConvertor;
use Illuminate\Console\Command;

class TurnOffSnoozeAccount extends Command
{
    use DateConvertor;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:turn-off-snooze';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for deactivate snoozed account to turn off ';
    protected $usersRepository;
    protected $usersSnoozeRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        UsersRepositoryEloquent $usersRepository,
        UsersSnoozeRepositoryEloquent $usersSnoozeRepository
    ) {
        parent::__construct();
        $this->usersRepository = $usersRepository;
        $this->usersSnoozeRepository = $usersSnoozeRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        # 1 get current date 
        $currentDate = $this->getCurrentDateUTC();

        # 2 get snoozed details where end date is less then current date
        $allSnoozedAccount = $this->usersSnoozeRepository->getDetailsByInput(
            [
                'end_date' => $currentDate,
                'list' => ['id', 'user_id', 'start_date', 'end_date']
            ]
        );

        # 3 set to user details is_snooze to false for all snoozed user account
        foreach ($allSnoozedAccount as $key => $snoozedAccount) {

            # 4 get all users by snoozed relation
            $user = $this->usersRepository->getDetailsByInput([
                'id' => $snoozedAccount->user_id,
                'list' => ['id', 'is_snooze'],
                'first' => true
            ]);

            # 5 update user account to snooze off
            $user->is_snooze = false; // set snooze to off
            $user->save(); // and save it

            # 6 also remove entry from user snooze table
            $this->usersSnoozeRepository->deleteWhere([
                'user_id' => $snoozedAccount->user_id
            ]);
            $this->info($snoozedAccount->user_id .  " - id user account has been snoozed off.");
        }
    }
}
