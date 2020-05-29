<?php

namespace App\Http\Controllers\API\v1\LoadCenter;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\LoadCenterFeedReportsRepositoryEloquent;
use App\Libraries\Repositories\LoadCenterFeedUsersReportRepositoryEloquent;
use App\Models\LoadCenterFeedUsersReport;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{

    protected $userID;
    protected $loadCenterFeedReportsRepository;
    protected $loadCenterFeedUsersReportRepository;

    public function __construct(
        LoadCenterFeedReportsRepositoryEloquent $loadCenterFeedReportsRepository,
        LoadCenterFeedUsersReportRepositoryEloquent $loadCenterFeedUsersReportRepository
    ) {
        $this->userID = Auth::id();
        $this->loadCenterFeedReportsRepository = $loadCenterFeedReportsRepository;
        $this->loadCenterFeedUsersReportRepository = $loadCenterFeedUsersReportRepository;
    }

    public function getAllReports(): \Illuminate\Http\JsonResponse
    {
        $reports = $this->loadCenterFeedReportsRepository->getDetails([
            'is_active' => true,
            'sort_by' => ['sequence', 'asc'],
            'list' => ['id', 'name', 'is_active', 'sequence']
        ]);
        if (isset($reports) && $reports['count'] === 0) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'Reports']));
        }
        return $this->sendSuccessResponse($reports, __('validation.common.details_found', ['module' => 'Reports']));
    }

    public function addReportOnFeed(Request $request)
    {
        $input = $request->all();

        $input['user_id'] = $input['user_id'] ?? $this->userID;

        /** check model validation */
        $validation = LoadCenterFeedUsersReport::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->makeError(null, $validation->errors()->first());
        }

        /** check already added with same  */
        $addedUserReport = $this->loadCenterFeedUsersReportRepository->checkAlreadyAddedReport(array_merge($input, ['first' => true]));
        if (isset($addedUserReport)) {
            return $this->sendBadRequest(null, __('validation.common.already_added', ['module' => 'report']));
        }

        /** create user report for feed. */
        $createReport = $this->loadCenterFeedUsersReportRepository->create($input);
        return $this->sendSuccessResponse($createReport, __('validation.common.added', ['module' => "Report"]));
    }
}
