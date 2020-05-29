<?php

namespace App\Http\Controllers\API\v1\Bookmark;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookmark\BookmarkRequest;
use App\Libraries\Repositories\BookmarkRepositoryEloquent;
use App\Libraries\Repositories\SpecializationsRepositoryEloquent;
use App\Models\Bookmark;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookmarkController extends Controller
{
    protected $bookmarkRepository;
    protected $specializationsRepository;
    protected $userId;

    protected $ProfessionalsName = "Professionals";
    protected $EventsName = "Events";

    public function __construct(
        BookmarkRepositoryEloquent $bookmarkRepository,
        SpecializationsRepositoryEloquent $specializationsRepository
    ) {
        $this->userId = Auth::id();
        $this->bookmarkRepository = $bookmarkRepository;
        $this->specializationsRepository = $specializationsRepository;
    }

    public function list(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = $input['user_id'] ?? $this->userId;
        $bookmarks = $this->bookmarkRepository->getDetails($input);

        if (isset($bookmarks) && $bookmarks['count'] == 0) return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Bookmark"]));

        /** get all professional profiles */
        $professionals = collect($bookmarks['list'])->reject(function ($book) {
            return empty($book->professional_id);
        });

        /** get all event bookmarks */
        $events = collect($bookmarks['list'])->reject(function ($book) {
            return empty($book->event_id);
        });

        /** made final response for event and professional */
        // $fullResponse = [];
        if ($professionals->first() !== null) {
            $fullResponse[] = [
                'name' => $this->ProfessionalsName,
                "data" => array_values($professionals->toArray())
            ];
        }
        if ($events->first() !== null) {
            $fullResponse[] = [
                'name' => $this->EventsName,
                "data" => array_values($events->toArray())
            ];
        }

        return $this->sendSuccessResponse($fullResponse ?? null, __('validation.common.details_found', ['module' => 'Bookmark']));
    }

    public function listViewAll(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = $input['user_id'] ?? $this->userId;

        $validation = $this->requiredValidation(['name'], $input);
        if (isset($validation) && $validation['flag'] == false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        $bookmarks = $this->bookmarkRepository->getDetailsByInput($input);
        if (isset($bookmarks) && count($bookmarks) == 0) return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Bookmark"]));

        $eventsList = [];
        $professionalsProfile = [];
        if ($input['name'] == $this->EventsName) {
            /** get all event bookmarks */
            $events = collect($bookmarks)->reject(function ($book) {
                return empty($book->event_id);
            });
            foreach ($events as $key => $bookmark) {
                $bookmark = $bookmark->toArray();
                $eventsList[] = $bookmark['event_detail'];
                // dd('f book', collect($bookmark)->pluck('event_detail'));
            }
            return $this->sendSuccessResponse($eventsList, __('validation.common.details_found', ['module' => $this->EventsName]));
        } elseif ($input['name'] == $this->ProfessionalsName) {
            /** get all event professional profiles */
            $professionals = collect($bookmarks)->reject(function ($book) {
                return empty($book->professional_id);
            });
            foreach ($professionals as $key => $bookmark) {
                $bookmark = $bookmark->toArray();
                $bookmark['professional_detail']['specialization_details'] = $this->specializationsRepository->getDetailsByInput([
                    'ids' => $bookmark['professional_detail']['specialization_ids']
                ]);
                $professionalsProfile[] = $bookmark['professional_detail'];
                // dd('f book',   collect($bookmark)->pluck('professional_detail'));
            }
            return $this->sendSuccessResponse($professionalsProfile, __('validation.common.details_found', ['module' => $this->ProfessionalsName]));
        }
        // $allProfessional = collect($professionals)->pluck('professional_detail')->all();
        // $allEvents = collect($professionals)->pluck('event_detail')->all();
    }

    public function createOrDelete(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = $input['user_id'] ?? $this->userId;

        /** check model validation */
        $validation = Bookmark::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        if (isset($input['is_create']) && $input['is_create'] == true) {
            # Create code...
            try {
                $bookmark = $this->commonCreateUpdate($input);
                return $this->sendSuccessResponse($bookmark, __('validation.common.saved', ['module' => "Bookmark"]));
            } catch (Exception $exception) {
                return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'bookmark']));
            }
        } else {
            # Delete code...
            try {
                $this->bookmarkRepository->deleteWhere([
                    'user_id' => $input['user_id'],
                    'professional_id' => $input['professional_id'] ?? null,
                    'event_id' => $input['event_id'] ?? null
                ]);
                return $this->sendSuccessResponse(null, __('validation.common.removed', ['module' => 'bookmark']));
            } catch (Exception $exception) {
                return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'bookmark']));
            }
        }
    }


    // Request $request
    // BookmarkRequest $request
    public function store(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = $input['user_id'] ?? $this->userId;

        // $validation = $request->validated();
        /** check model validation */
        $validation = Bookmark::validation($input);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

        /** check if already event or professional already exists or not */
        $bookmark = $this->bookmarkRepository->getDetailsByInput(
            [
                'professional_id' => $input['professional_id'] ?? null,
                'event_id' => $input['event_id'] ?? null,
                'user_id' => $input['user_id'] ?? null,
                'first' => true
            ]
        );
        if (isset($bookmark)) {
            return $this->sendBadRequest(null, __('validation.common.key_already_exist', ['key' => "Bookmark"]));
        }

        try {
            $bookmark = $this->commonCreateUpdate($input);
        } catch (Exception $exception) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'Event or Professional']));
            Log::error($exception->getMessage());
        }

        return $this->sendSuccessResponse($bookmark, __('validation.common.saved', ['module' => "Bookmark"]));
    }

    public function commonCreateUpdate($input, $id =  null)
    {
        return $this->bookmarkRepository->updateOrCreate(
            [
                'id' => $id
            ],
            $input
        );
    }

    /**
     * Remove from bookmark
     */
    public function destroy($id)
    {
        try {
            $this->bookmarkRepository->delete($id);
            return $this->sendSuccessResponse(null, __('validation.common.removed', ['module' => 'bookmark']));
        } catch (Exception $exception) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => 'bookmark']));
        }
    }
}
