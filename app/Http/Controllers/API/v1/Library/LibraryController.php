<?php

/** @noinspection ALL */

namespace App\Http\Controllers\API\v1\Library;

use App\Models\Library;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Repositories\LibraryRepositoryEloquent;
use App\Libraries\Repositories\BodyPartRepositoryEloquent;
use App\Libraries\Repositories\TrainingLogRepositoryEloquent;
use App\Libraries\Repositories\CommonLibraryRepositoryEloquent;

class LibraryController extends Controller
{
    protected $moduleName = "Library";
    protected $userId;

    protected $libraryRepository;
    protected $trainingLogRepository;
    protected $bodyPartRepository;
    protected $commonLibraryRepository;

    public function __construct(
        LibraryRepositoryEloquent $libraryRepository,
        TrainingLogRepositoryEloquent $trainingLogRepository,
        BodyPartRepositoryEloquent $bodyPartRepository,
        CommonLibraryRepositoryEloquent $commonLibraryRepository
    ) {
        $this->userId = Auth::id();
        $this->libraryRepository = $libraryRepository;
        $this->trainingLogRepository = $trainingLogRepository;
        $this->bodyPartRepository = $bodyPartRepository;
        $this->commonLibraryRepository = $commonLibraryRepository;
    }

    /**
     * list => get library details
     *
     * @param mixed $request
     *
     * @return void
     */
    public function list(Request $request)
    {
        $input = $request->all();
        $validation = $this->requiredValidation(['status', "user_id"], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        /** check for favorite list */
        if (isset($input['status']) && $input['status'] == LIBRARY_LIST_FAVORITE) {
            $input['is_favorite'] = true;
        } else {
            // updated to multiple status
            $bodyPart = $this->bodyPartRepository->getDetailsByInput(['code' => $input['status'], 'first' => true, 'list' => ['id', 'display_id']]);
            //            dd("Body Parts", $bodyPart->toArray());
            if (isset($bodyPart)) {
                $input['regions_ids'] = [$bodyPart->id];
                $input['category_id'] = $bodyPart->id;
            }
            $commonLibraries = $this->commonLibraryRepository->getDetails([
                'relation' => $input['relation'],
                'category_id' => $bodyPart->id,
                'search' => $input['search'] ?? null,
                "search_from" => $input['search_from'] ?? null
            ]);
        }

        $libraries = $this->libraryRepository->getDetails($input);
        // dd('all lub',  $libraries['list']->toArray());
        if (isset($libraries) && $libraries['count'] === 0 && isset($commonLibraries) && $commonLibraries['count'] === 0) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $libraries['list'] = $libraries['list']->toArray();
        if (isset($commonLibraries) && $commonLibraries['count'] > 0) {
            $libraries['list'] = array_merge($libraries['list'], $commonLibraries['list']->toArray());
        }
        /** for give group wise listing of library listing else normal listing. */
        if (isset($input['status'], $input['is_group_wise']) && $input['status'] != LIBRARY_LIST_FAVORITE && $input['is_group_wise'] == true) {
            /** filter all sub part related status parent id data */
            $getRegions = $this->bodyPartRepository->getDetailsByInput(['parent_id' => $bodyPart->id, $input, "is_active" => true]);
            if (isset($getRegions) && count($getRegions) == 0) {
                return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => "body parts"]));
            }

            $response = [];
            foreach ($getRegions->toArray() as $region) {
                foreach ($libraries['list'] as $library) {
                    $firstRegionId = $library['regions_ids'][0];

                    /** if common from common libraries */
                    if (isset($library['sub_header_id'])) {
                        if ($library['sub_header_id'] == $region['id'])
                            $allLibraries[] = $library;
                        /** else get from custom libraries */
                    }
                    else {
                        if ($firstRegionId == $region['id']) {
                            if (isset($region['display_id'])) {
                                $resIndex = collect($response)->search(function ($item, $key) use ($region) {
                                    return $item['id'] == $region['display_id'];
                                });
                                if (isset($resIndex) && $resIndex >= 0) {
                                    $response[$resIndex]['data'][] = $library;
                                    break;
                                }
                                /**
                                 * Return this response if data was found ,
                                 * No need to proceed next process.
                                 */
                            } else {
                                $allLibraries[] = $library;
                            }
                        }
                        // else {
                        //     $getFirstRegionOfLibrary = $this->bodyPartRepository->getDetailsByInput([
                        //         'id' => $firstRegionId,
                        //         'list' => [ "id", "display_id"],
                        //         'first' => true
                        //     ]);
                        //     if (isset($getFirstRegionOfLibrary, $getFirstRegionOfLibrary->display_id)) {
                        //         $resIndex = collect($response)->search(function ($item, $key) use ($getFirstRegionOfLibrary, $response) {
                        //             // dd('c data', $response, $item, $getFirstRegionOfLibrary->toArray());
                        //             return isset($item['id']) && $item['id'] == $getFirstRegionOfLibrary->display_id;
                        //         });
                        //         // echo " " . $resIndex;
                        //         if (isset($resIndex) && $resIndex >= 0) {
                        //             $response[$resIndex]['data'][] = $library;
                        //             break;
                        //         } 
                        //     }
                        //      /** if first not found then check display id in array */
                        // }
                    }
                }
                array_push($response, [
                    'id' => $region['id'],
                    'name' => $region['name'],
                    'type' => $region['type'],
                    "data" => $allLibraries ?? []
                ]);
                unset($allLibraries);
            }
            $libraries['list'] = $response;
        }
        return $this->sendSuccessResponse($libraries, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    /**
     * store => store library  // NOTE remain to update RM
     *
     * @param mixed $request
     *
     * @return void
     */
    public function store(Request $request)
    {
        $input = $request->all();

        /** check validation */
        $validator = Library::validation($input);
        if ($validator->fails()) {
            return $this->sendBadRequest(null, $validator->errors()->first());
        }

        $library = $this->createUpdateLibraries($input);
        return $this->sendSuccessResponse($library, __("validation.common.created", ['module' => $this->moduleName]));
    }

    /**
     * createUpdateLibraries => create or update libraries by id
     *
     * @param mixed $input => input data
     * @param mixed $id => if updated records id
     *
     * @return void
     */
    public function createUpdateLibraries($input, $id = null)
    {
        $library = $this->libraryRepository->updateOrCreate(
            [
                'id' => $id,
            ],
            $input
        );
        return $library->fresh();
    }

    /**
     * show => get library details by id
     *
     * @param mixed $id
     *
     * @return void
     */
    public function show($id)
    {
        $library = $this->libraryRepository->getDetailsByInput([
            'id' => $id,
            'first' => true
        ]);

        if (!isset($library)) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        $library = $library->toArray();
        /** generate graph data */
        // $library['graph_data'] = $this->generateGraphData($id);
        return $this->sendSuccessResponse($library, __('validation.common.details_found', ['module' => $this->moduleName]));
    }

    public function getGraphDetails(Request $request, $id)
    {
        $input = $request->all();
        $library = $this->generateGraphData($id, $input);
        return $this->sendSuccessResponse($library, __('validation.common.details_found', ['module' => "Graph"]));
    }

    public function generateGraphData($id = null, $input)
    {
        /** get last 30 days resistance logs with current user.*/
        $logRequest = [
            'user_id' => $this->userId,
            'status' => TRAINING_LOG_STATUS_RESISTANCE,
            'start_date' => $input['start_date'] ?? $this->getLast30DayDate(),
            'end_date' => $input['end_date'] ?? $this->getCurrentDateUTC(),
        ];

        $logDetails = $this->trainingLogRepository->getDetailsByInput($logRequest);

        /** check if data not found then don't need to generate graph data */
        if (isset($logDetails) && count($logDetails) == 0) {
            return [];
        }
        $graphResponse = [];

        foreach ($logDetails->toArray() as $key => $log) {
            /** check in data for libraries id from this library */
            if (isset($input['common_library_id'])) {
                $data = collect($log['exercise'])->where('common_library_id', '=', $input['common_library_id'])->pluck('data')->all();
            } elseif (isset($input['library_id'])) {
                $data = collect($log['exercise'])->where('library_id', '=', $input['library_id'])->pluck('data')->all();
            } else {
                $data = collect($log['exercise'])->where('library_id', '=', $id)->pluck('data')->all();
            }
            $counterData = collect($data)->map(function ($item) {
                return (int) collect($item)->sum('weight');
            });
            $graphResponse[] = [
                'date' => $log['date'],
                'total_volume' => (int) array_sum($counterData->toArray())
            ];
        }
        return $graphResponse;
    }

    /**
     * update => Update Library data Using ID
     *
     * @param mixed $id
     * @param mixed $request
     *
     * @return void
     */
    public function update($id, Request $request)
    {
        /** check id is exists or not */
        $library = $this->libraryRepository->findByField('id', $id);
        if (isset($library) && count($library) == 0) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        /** store all request to input */
        $input = $request->all();

        /** check validation */
        $validator = Library::validation($input);
        if ($validator->fails()) {
            return $this->sendBadRequest(null, $validator->errors()->first());
        }

        $library = $this->createUpdateLibraries($input, $id);
        return $this->sendSuccessResponse($library, __('validation.common.updated', ['module' => $this->moduleName]));
    }

    /**
     * setToFavorite => set to favorite page
     *
     * @param mixed $id
     * @param mixed $request
     *
     * @return void
     */
    public function setToFavorite($id, Request $request)
    {
        /** check id is exists or not */
        $library = $this->libraryRepository->findByField('id', $id);
        if (isset($library) && count($library) == 0) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }
        $library = $library->first();

        $input = $request->all();

        $validation = $this->requiredValidation(['is_favorite'], $input);
        if (isset($validation) && $validation['flag'] === false) {
            return $this->sendBadRequest(null, $validation['message']);
        }

        $library->is_favorite = $input['is_favorite'];
        $library->save();
        // $library->fresh();
        return $this->sendSuccessResponse(null, __('validation.common.saved', ['module' => $this->moduleName]));
    }

    /**
     * destroy => delete library
     *
     * @param mixed $id
     *
     * @return void
     */
    public function destroy($id)
    {
        /** check id is exists or not */
        $library = $this->libraryRepository->findByField('id', $id);
        if (isset($library) && count($library) == 0) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ['module' => $this->moduleName]));
        }

        $this->libraryRepository->delete($id);
        return $this->sendSuccessResponse(null, __('validation.common.module_deleted', ['module' => $this->moduleName]));
    }
}
