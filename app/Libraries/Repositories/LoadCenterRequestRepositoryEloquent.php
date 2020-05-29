<?php

namespace App\Libraries\Repositories;

use App\Models\LoadCenterRequest;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

class LoadCenterRequestRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return LoadCenterRequest::class;
    }

    /**
     * boot => Boot up the repository, pushing criteria
     *
     * @return void
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
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
            $value = $this->customSearch($value, $input, ['title', 'yourself']);
        }

        $this->customRelation($value, $input, []); //'user_detail'

        if (isset($input['exercise_name'])) {
            $value = $value->where('exercise_name', $input['exercise_name']);
        }

        /** id and ids wise filter */
        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }
        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** user_id and user_ids wise filter  */
        if (isset($input['user_id'])) {
            $value = $value->where('user_id', $input['user_id']);
        }
        if (isset($input['user_ids']) && is_array($input['user_ids']) && count($input['user_ids'])) {
            $value = $value->whereIn('user_id', $input['user_ids']);
        }

        /** filter */
        if (isset($input['visible_to'])) {
            $value = $value->where('visible_to', $input['visible_to']);
        }
        if (isset($input['event_name'])) {
            $value = $value->where('event_name', $input['event_name']);
        }
        if (isset($input['event_price'])) {
            $value = $value->where('event_price', $input['event_price']);
        }

        /** date wise records */
        if (isset($input['start_date'])) {
            $value = $value->where('date', ">=", $input['start_date']);
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

        /** get first or all records */
        if (isset($input['first']) && ($input['first'] === true)) {
            $value = $value->first();
        } elseif (isset($input['is_deleted']) && $input['is_deleted'] === true) {
            $value = $value->withTrashed()->get();
        } else {
            $value = $value->get();
        }
    }

    /**
     * getDetails => get details for listing
     *
     * @param mixed $input
     *
     * @return array
     */
    public function getDetails($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);
        $count = $value->count();
        $this->getCommonPaginationFilterFn($value, $input);
        return [
            'count' => $count,
            'list' => $value
        ];
    }

    /**
     * getDetailsByInput => get details by input
     *
     * @param mixed $input
     *
     * @return void
     */
    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);

        /** check if only count key is true the return only count */
        if (isset($input['is_count']) && $input['is_count'] === true) return $value->count();

        $this->getCommonPaginationFilterFn($value, $input);
        return $value;
    }

    /**
     * updateRich => update some keys
     *
     * @param mixed $input => updated input
     * @param mixed $id => update id record
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
}
