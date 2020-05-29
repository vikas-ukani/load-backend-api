<?php

namespace App\Http\Controllers\API\v1\Notifications;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\NotificationsRepositoryEloquent;

class NotificationsController extends Controller
{
    protected $moduleName = "Notification";

    protected $notificationRepository;

    /**
     * __construct
     *
     * @param  mixed $notificationRepository
     *
     * @return void
     */
    public function __construct(NotificationsRepositoryEloquent $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * listDetails => get notification listing details
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function listDetails(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = \Auth::id();
        $notifications = $this->notificationRepository->getDetails($input);
        if (isset($notifications) && $notifications['count'] == 0) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ["module" => $this->moduleName]));
        }
        return $this->sendSuccessResponse($notifications, __("validation.common.details_found", ["module" => $this->moduleName]));
    }

    /**
     * readNotification => set to read notifications
     *
     * @param  mixed $id
     *
     * @return void
     */
    public function readNotification($id)
    {
        try {
            $date = $this->getCurrentDateUTC();
            /** update notification */
            $notification = $this->notificationRepository->updateRich([
                'read_at' => $date
            ], $id);
            return $this->sendSuccessResponse($notification, __("validation.common.saved", ["module" => $this->moduleName]));
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage());
            return $this->sendBadRequest(null, $exception->getMessage());
        }
    }
}
