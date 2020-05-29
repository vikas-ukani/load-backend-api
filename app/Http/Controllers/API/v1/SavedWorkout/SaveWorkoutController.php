<?php

namespace App\Http\Controllers\API\v1\SavedWorkout;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Repositories\SavedWorkoutsRepositoryEloquent;

class SaveWorkoutController extends Controller
{
    protected $moduleName = "Saved Workout";

    protected $savedWorkoutRepository;

    public function __construct(SavedWorkoutsRepositoryEloquent $savedWorkoutRepository)
    {
        $this->savedWorkoutRepository = $savedWorkoutRepository;
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

        $savedWorkouts = $this->savedWorkoutRepository->getDetails($input);
        if (isset($savedWorkouts) && $savedWorkouts['count'] == 0) {
            return $this->sendBadRequest(null, __("validation.common.details_not_found", ["module" => $this->moduleName]));
        }
        $data = collect($savedWorkouts['list']->toArray())->pluck('training_log')->toArray();
        $data = array_values(array_filter($data));


        $return = [
            'count' => count($data),
            'list' => $data
        ];

        return $this->sendSuccessResponse($return, __("validation.common.details_found", ["module" => $this->moduleName]));
    }
}
