<?php

namespace App\Http\Controllers\API\v1\Library;

use App\Http\Controllers\Controller;
use App\Libraries\Repositories\CustomCommonLibraryDetailsRepositoryEloquent;
use App\Libraries\Repositories\LibraryRepositoryEloquent;
use App\Models\CustomCommonLibrariesDetails;
use Illuminate\Http\Request;

class CustomCommonLibraryDetailController extends Controller
{

    protected $userId;
    protected $libraryRepository;
    protected $customCommonLibraryDetailsRepository;

    public function __construct(
        LibraryRepositoryEloquent $libraryRepository,
        CustomCommonLibraryDetailsRepositoryEloquent $customCommonLibraryDetailsRepository
    ) {
        $this->userId = \Auth::id();
        $this->libraryRepository = $libraryRepository;
        $this->customCommonLibraryDetailsRepository = $customCommonLibraryDetailsRepository;
    }

    public function show($id)
    {
        /** id from common libraries details */
        $customCommonLibrariesDetails = $this->customCommonLibraryDetailsRepository->getDetailsByInput([
            'user_id' => $this->userId,
            'common_libraries_id' => $id,
            'first' => true
        ]);

        if (!!!isset($customCommonLibrariesDetails)) {
            return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Library"]));
        }

        return $this->sendSuccessResponse($customCommonLibrariesDetails, __('validation.common.details_found', ['module' => "Library"]));
    }

    public function createUpdateCustomCommonLibraryDetails(Request $request)
    {
        $input = $request->all();

        /** check if library is created by own user then update data to user's library */
        if (isset($input['libraries_id'])) {
            $library = $this->libraryRepository->updateRich([
                'is_show_again_message' => $input['is_show_again_message'],
                'repetition_max' => $input['repetition_max']
            ], $input['libraries_id']);

            return $this->sendSuccessResponse($library, __('validation.common.saved', ['module' => 'library']));
        } else {
            /** else update to common details of library. */
            $returnData = $this->createUpdateCommon($input);
            if (isset($returnData) && $returnData['flag'] === false) {
                return $this->sendBadRequest(null, $returnData['message']);
            }
            return $this->sendSuccessResponse($returnData['data'], $returnData['message']);
        }
    }

    /**
     * @param $input
     * @param null $id
     * @return array
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function createUpdateCommon($input, $id = null)
    {
        $input['user_id'] = $input['user_id'] ?? $this->userId;

        /** check model validation */
        $validation = CustomCommonLibrariesDetails::validation($input, $id);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->makeError(null, $validation->errors()->first());
        }
        $createUpdate = $this->customCommonLibraryDetailsRepository->updateOrCreate(
            [
                'user_id' => $input['user_id'],
                'common_libraries_id' => $input['common_libraries_id'],
            ],
            $input
        );
//         try {
//            $createUpdate = $this->customCommonLibraryDetailsRepository->updateOrCreate(
//                [
//                    'user_id' => $input['user_id'],
//                    'common_libraries_id' => $input['common_libraries_id'],
//                ],
//                $input
//            );
//        } catch (\Exception $exception) {
//            \Log::error($exception->getLine() . $exception->getMessage());
//            return $this->makeError(null, $exception->getLine() . $exception->getMessage());
//        }

        return $this->makeResponse($createUpdate, __('validation.common.saved', ['module' => 'Library']));

    }
}
