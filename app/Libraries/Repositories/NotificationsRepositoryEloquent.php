<?php

namespace App\Libraries\Repositories;

use App\Models\Notifications;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

/**
 * Class NotificationsRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class NotificationsRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Notifications::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    /**
     * commonFilterFn => make common filter for list and getDetailsByInput
     *
     * @param  mixed $value
     * @param  mixed $input
     *
     * @return void
     */
    protected function commonFilterFn(&$value, $input)
    {

        /** searching */
        if (isset($input['search'])) {
            $value = $this->customSearch($value, $input, ['id', 'title', 'message', 'read_at', 'user_id']);
        }

        /** send relation keys in last param */
        $this->customRelation($value, $input);

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

        /** check for read at is null */
        if (isset($input['is_read']) && $input['is_read'] == true) {
            $value = $value->whereNull('read_at');
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
        if (isset($input['list'])) {
            $value = $value->select($input['list']);
        }

        // if (isset($input['page']) && isset($input['limit'])) {
        //     $value = $this->customPaginate($value, $input);
        // }

        if (isset($input['limit'])) {
            $value = $value->take($input['limit']);
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

    /**
     * getDetails => Get Details from DB
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getDetails($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);
        /** for id wise pagination  */
        if (isset($input['last_id']) && $input['last_id'] >= 1) {
            $value = $value->where('id', '<', $input['last_id']);
        }

        /** get counts */
        $count = $value->count();
        /** for id wise pagination */
        if (isset($input['last_id']) && $input['last_id'] == 0) {
            $value = $value->skip(0);
        }
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

    /**
     * getDetailsByInput => get details by input
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);
        $this->getCommonPaginationFilterFn($value, $input);
        return $value;
    }
}
