<?php

namespace App\Libraries\Repositories;

use App\Models\SavedWorkout;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

/**
 * Class SavedWorkoutsRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class SavedWorkoutsRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SavedWorkout::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }



    /**
     * getCommonPaginationFilterFn => get pagination and get data
     *
     * @param  mixed $value
     * @param  mixed $input
     *
     * @return void
     */
    protected function getCommonPaginationFilterFn(&$value, $input)
    {
        /** get list selected only */
        if (isset($input['list'])) {
            $value = $value->select($input['list']);
        }

        /** pagination's */
        if (isset($input['page']) && isset($input['limit'])) {
            $value = $this->customPaginate($value, $input);
        }
        /** sorting accenting or descending   */
        if (isset($input['sort_by']) && count($input['sort_by']) > 0) {
            $value = $value->orderBy($input['sort_by'][0], $input['sort_by'][1]);
        } else {
            $value = $value->ordered();
        }
        if (isset($input['first']) && $input['first'] == true) {
            $value = $value->first();
        } else if (isset($input['is_deleted']) && $input['is_deleted'] == true) {
            $value = $value->withTrashed()->get();
        } else {
            $value = $value->get();
        }
    }


    /**
     * getDetails => Get Details from db
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getDetails($input = null)
    {
        $value = $this->makeModel();

        /** searching */
        if (isset($input['search'])) {
            $value = $this->customSearch($value, $input, ['id', 'training_log_id', 'user_id']);
        }

        // if (isset($input['relation']) && in_array( 'training_log', $input[ 'relation'])) {
        $input['training_log_where'] = ['is_log' => false];
        // }
        /** send relation keys in last param */
        $this->customRelation($value, $input, ['user_detail', 'training_log']);

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }

        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** training log id wise filter */
        if (isset($input['training_log_id'])) {
            $value = $value->where('training_log_id', $input['training_log_id']);
        }
        if (isset($input['training_log_ids']) && is_array($input['training_log_ids']) && count($input['training_log_ids'])) {
            $value = $value->whereIn('training_log_id', $input['training_log_ids']);
        }

        /** user id wise filter */
        if (isset($input['user_id'])) {
            $value = $value->where('user_id', $input['user_id']);
        }
        if (isset($input['user_ids']) && is_array($input['user_ids']) && count($input['user_ids'])) {
            $value = $value->whereIn('user_id', $input['user_ids']);
        }

        /** date wise records */
        if (isset($input['start_date'])) {
            $value = $value->where('created_at', ">=", $input['start_date']);
        }

        $count = $value->count();

        $this->getCommonPaginationFilterFn($value, $input);

        return [
            'count' => $count,
            'list' => $value
        ];
    }

    /**
     * updateRich => update some keys 
     *
     * @param  mixed $input => updated input
     * @param  mixed $id => update id record
     *
     * @return void
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

    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();

        /** searching */
        if (isset($input['search'])) {
            $value = $this->customSearch($value, $input, ['id', 'training_log_id', 'user_id']);
        }

        /** send relation keys in last param */
        $this->customRelation($value, $input, []);

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }

        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** training log id wise filter */
        if (isset($input['training_log_id'])) {
            $value = $value->where('training_log_id', $input['training_log_id']);
        }
        if (isset($input['training_log_ids']) && is_array($input['training_log_ids']) && count($input['training_log_ids'])) {
            $value = $value->whereIn('training_log_id', $input['training_log_ids']);
        }

        /** user id wise filter */
        if (isset($input['user_id'])) {
            $value = $value->where('user_id', $input['user_id']);
        }
        if (isset($input['user_ids']) && is_array($input['user_ids']) && count($input['user_ids'])) {
            $value = $value->whereIn('user_id', $input['user_ids']);
        }

        /** date wise records */
        if (isset($input['start_date'])) {
            $value = $value->where('created_at', ">=", $input['start_date']);
        }

        $this->getCommonPaginationFilterFn($value, $input);
        return $value;
    }
}
