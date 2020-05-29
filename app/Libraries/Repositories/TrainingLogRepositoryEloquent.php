<?php

/** @noinspection ALL */

namespace App\Libraries\Repositories;

use App\Libraries\RepositoriesInterfaces\UsersRepository;
use App\Models\TrainingLog;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class UsersRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class TrainingLogRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return TrainingLog::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * getDetails => get list details
     *
     * @param mixed $input
     *
     * @return void
     */
    public function getDetails($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);
        $count = $value->count();
        $this->getCommonPaginationFilterFn($value, $input);
        // return $value;
        return [
            'count' => $count,
            'list' => $value
        ];
    }

    /**
     * commonFilterFn => make common filter for list and getDetailsByInput
     *
     * @param mixed $value
     * @param mixed $input
     *
     * @return void
     */
    protected function commonFilterFn(&$value, $input)
    {
        /** searching */
        if (isset($input['search'])) {
            $value = $this->customSearch($value, (array) $input, ['workout_name', 'targeted_hr', 'notes', 'comments']);
        }

        /** send relation keys in last param */
        $this->customRelation($value, $input, []);

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }
        if (isset($input['ids']) && count($input['ids']) > 0) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** user id wise filter  */
        if (isset($input['user_id'])) {
            $value = $value->where('user_id', $input['user_id']);
        }
        if (isset($input['user_ids']) && is_array($input['user_ids']) && count($input['user_ids'])) {
            $value = $value->whereIn('user_id', $input['user_ids']);
        }

        /** training_activity_id wise filter  */
        if (isset($input['training_activity_id'])) {
            $value = $value->where('training_activity_id', $input['training_activity_id']);
        }
        if (isset($input['training_activity_ids']) && is_array($input['training_activity_ids']) && count($input['training_activity_ids'])) {
            $value = $value->whereIn('training_activity_id', $input['training_activity_ids']);
        }

        /** training_goal_id wise filter  */
        if (isset($input['training_goal_id'])) {
            $value = $value->where('training_goal_id', $input['training_goal_id']);
        }
        if (isset($input['training_goal_ids']) && is_array($input['training_goal_ids']) && count($input['training_goal_ids'])) {
            $value = $value->whereIn('training_goal_id', $input['training_goal_ids']);
        }

        if (isset($input['training_goal_custom'])) {
            $value = $value->where('training_goal_custom', $input['training_goal_custom']);
        }
        if (isset($input['training_goal_custom_id'])) {
            $value = $value->where('training_goal_custom_id', $input['training_goal_custom_id']);
        }

        /** training_intensity_id wise filter  */
        if (isset($input['training_intensity_id'])) {
            $value = $value->where('training_intensity_id', $input['training_intensity_id']);
        }
        if (isset($input['training_intensity_ids']) && is_array($input['training_intensity_ids']) && count($input['training_intensity_ids'])) {
            $value = $value->whereIn('training_intensity_id', $input['training_intensity_ids']);
        }

        if (isset($input['status'])) {
            $value = $value->where('status', $input['status']);
        }

        if (isset($input['is_log'])) {
            $value = $value->where('is_log', $input['is_log']);
        }

        /** check latitude from database */
        if (isset($input['latitude'])) {
            $value = $value->where('latitude', $input['latitude']);
        }

        /** check longitude from database */
        if (isset($input['longitude'])) {
            $value = $value->where('longitude', $input['longitude']);
        }

        if (isset($input['is_complete'])) {
            $value = $value->where('is_complete', $input['is_complete']);
        }
        if (isset($input['RPE'])) {
            $value = $value->where('RPE', $input['RPE']);
        }

        /** date wise records filtering*/
        if (isset($input['start_date'])) {
            $value = $value->where('date', ">=", $input['start_date']);
        }
        if (isset($input['end_date'])) {
            $value = $value->where('date', "<=", $input['end_date']);
        }
    }

    /**
     * getCommonPaginationFilterFn => get pagination and get data
     *
     * @param mixed $value
     * @param mixed $input
     *
     * @return void
     */
    protected function getCommonPaginationFilterFn(&$value, $input)
    {
        if (isset($input['list'])) {
            $value = $value->select($input['list']);
        }

        if (isset($input['page']) && isset($input['limit'])) {
            $value = $this->customPaginate($value, $input);
        }
        if (isset($input['sort_by']) && count($input['sort_by']) > 0) {
            $value = $value->orderBy($input['sort_by'][0], $input['sort_by'][1]);
        } else {
            $value = $value->ordered();
        }

        if (isset($input['first']) && ($input['first'] === true)) {
            $value = $value->first();
        } elseif (isset($input['is_deleted']) && $input['is_deleted'] === true) {
            $value = $value->withTrashed()->get();
        } else {
            $value = $value->get();
        }
    }

    /** update some keys
     * @param $input
     * @param $id
     * @return mixed
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function updateRich($input, $id)
    {
        $value = $this->makeModel();
        $value = $value->whereId($id)->first();
        // $value->fill($input)->update();
        if (isset($value)) {
            $value->fill($input)->update();
            return $value->fresh();
        }
    }

    /** get details by input
     * @param null $input
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);
        $this->getCommonPaginationFilterFn($value, $input);
        return $value;
    }
}
