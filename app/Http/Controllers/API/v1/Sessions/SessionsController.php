<?php

namespace App\Http\Controllers\API\v1\Sessions;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\SessionsRepositoryEloquent;

class SessionsController extends Controller
{
    protected $moduleName = "session";

    protected $sessionsRepository;

    public function __construct(SessionsRepositoryEloquent $sessionsRepository)
    {
        $this->sessionsRepository = $sessionsRepository;
    }

    /**
     * listDetails => get details
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function listDetails(Request $request)
    {
        $input = $request->all();
        $sessions = $this->sessionsRepository->getDetails($input);
        if (isset($sessions) && $sessions['count'] == 0) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ["module" => $this->moduleName]));
        }
        return $this->sendSuccessResponse($sessions, __("validation.common.details_found", ["module" => $this->moduleName]));
    }

    /** update status for session move to completed */
}
