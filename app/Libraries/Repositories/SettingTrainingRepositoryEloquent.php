<?php

namespace App\Libraries\Repositories;

use App\Models\SettingTraining;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

/**
 * Class SettingTrainingRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class SettingTrainingRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SettingTraining::class;
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
            $value = $this->customSearch($value, $input, ['hr_max', 'height']);
        }

        /** send relation keys in last param */
        $this->customRelation($value, $input, []);

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }

        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** user_id and user_ids wise filter */
        if (isset($input['user_id'])) {
            $value = $value->where('user_id', $input['user_id']);
        }
        if (isset($input['user_ids']) && count($input['user_ids']) > 0) {
            $value = $value->whereIn('user_id', $input['user_ids']);
        }

        if (isset($input['hr_max'])) {
            $value = $value->where('hr_max', $input['hr_max']);
        }
        if (isset($input['hr_rest'])) {
            $value = $value->where('hr_rest', $input['hr_rest']);
        }

        if (isset($input['height'])) {
            $value = $value->where('height', $input['height']);
        }

        if (isset($input['weight'])) {
            $value = $value->where('weight', $input['weight']);
        }

        /** race_distance_id and race_distance_ids wise filter */
        if (isset($input['race_distance_id'])) {
            $value = $value->where('race_distance_id', $input['race_distance_id']);
        }
        if (isset($input['race_distance_ids']) && count($input['race_distance_ids']) > 0) {
            $value = $value->whereIn('race_distance_id', $input['race_distance_ids']);
        }

        if (isset($input['race_time'])) {
            $value = $value->where('race_time', $input['race_time']);
        }

        if (isset($input['name'])) {
            $value = $value->where('name', $input['name']);
        }

        if (isset($input['code'])) {
            $value = $value->where('code', $input['code']);
        }

        if (isset($input['is_active'])) {
            $value = $value->where('is_active', $input['is_active']);
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
     * @return object
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
