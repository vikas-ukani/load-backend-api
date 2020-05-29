<?php

namespace App\Http\Controllers;

use App\Models\WeekWiseFrequencyMaster;
use App\Models\WeekWiseWorkout;
use App\Models\WorkoutWiseLap;
use Illuminate\Http\Request;

class TestController extends Controller
{

    // Main
    public function insertPDFData(Request $request)
    {
        $input = $request->all();

        $validation = $this->requiredValidation(['frequency_master_data_1', 'workout_data_2', 'laps_data_3'], $input);
        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);

        // create frequency
        // $frequencyIds = $this->createFrequencyMaster($input);

        // create workouts
        // $workoutIds = $this->createWorkout($input, $frequencyIds);

        // create laps
        $workoutLapsIds = $this->createLaps($input, $workoutIds = []);
    }

    #table 1 FREQUENCY
    public function createFrequencyMaster($input = null)
    {
        $insertedIds = [];
        // create each frequency
        foreach ($input['frequency_master_data_1'] as $key => $value) {
            $createdFrequency = WeekWiseFrequencyMaster::create(
                $value
            );
            $insertedIds[] = $createdFrequency->id;
        }

        return $insertedIds;
    }

    #table 2 WORKOUT
    public function createWorkout($input = null, $frequencyIds)
    {
        foreach ($input['workout_data_2'] as $key => $value) {
            $value['week_wise_frequency_master_ids'] = $frequencyIds;
            $createdWorkout = WeekWiseWorkout::create(
                $value
            );
            $insertedIds[] = $createdWorkout->id;
        }
        return $insertedIds;
    }

    #table 3 LAPS
    public function createLaps($input = null, $workoutIds = null)
    {
        foreach ($input['laps_data_3'] as $key => $value) {
            $value['week_wise_workout_ids'] = $workoutIds;
            $createdWorkout = WorkoutWiseLap::create(
                $value
            );
            $insertedIds[] = $createdWorkout->id;
        }
        return $insertedIds;
    }
}
