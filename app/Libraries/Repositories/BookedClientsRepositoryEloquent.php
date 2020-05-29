<?php

namespace App\Libraries\Repositories;

use App\Models\BookedClients;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

/**
 * Class BookedClientsRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class BookedClientsRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return BookedClients::class;
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
     * @param  mixed $value
     * @param  mixed $input
     *
     * @return void
     */
    protected function commonFilterFn(&$value, $input)
    {
        /** searching */
        if (isset($input['search'])) {
            $value = $this->customSearch($value, $input);
        }

        /** from_id and from_ids wise filter */
        if (isset($input['from_id'])) {
            $value = $value->where('from_id', $input['from_id']);
        }
        if (isset($input['from_ids']) && count($input['from_ids']) > 0) {
            $value = $value->whereIn('from_id', $input['from_ids']);
        }

        /** to_id and to_ids wise filter */
        if (isset($input['to_id'])) {
            $value = $value->where('to_id', $input['to_id']);
        }
        if (isset($input['to_ids']) && count($input['to_ids']) > 0) {
            $value = $value->whereIn('to_id', $input['to_ids']);
        }

        if (isset($input['selected_date'])) {
            $value = $value->where('selected_date', $input['selected_date']);
        }

        /** available_time_id and available_time_ids wise filter */
        if (isset($input['available_time_id'])) {
            $value = $value->where('available_time_id', $input['available_time_id']);
        }
        if (isset($input['available_time_ids']) && count($input['available_time_ids']) > 0) {
            $value = $value->whereIn('available_time_id', $input['available_time_ids']);
        }

        if (isset($input['confirmed_status'])) {
            $value = $value->where('confirmed_status', $input['confirmed_status']);
        }

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }
        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** start date wise filter */
        if (isset($input['start_date'])) {
            $value = $value->where('selected_date', ">=", $input['start_date']);
        }
        /** end date wise filter */
        if (isset($input['end_date'])) {
            $value = $value->where('selected_date', '<=', $input['end_date']);
        }
        // if (isset($input['start_date'])) {
        //     $value = $value->where('created_at', ">=", $input['start_date']);
        // }

        /** send relation keys in last param */
        $this->customRelation($value, $input, []);
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
     * getDetails => Get Listing With Conditions wise
     *
     * @param  mixed $input
     *
     * @return void
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
     * getDetailsByInput => get pagination and get data query
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();

        /** common filter applied here */
        $this->commonFilterFn($value, $input);

        /** get pagination filter get data */
        $this->getCommonPaginationFilterFn($value, $input);

        return $value;
    }
}
