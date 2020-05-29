<?php

namespace App\Libraries\Repositories;

use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;
use App\Models\CommonProgramsWeek;

/**
 * Class CommonProgramsWeekRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class CommonProgramsWeekRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return CommonProgramsWeek::class;
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
            $value = $this->customSearch($value, $input, ['name', 'note', 'title']);
        }

        /** send relation keys in last param */
        $this->customRelation($value, $input, []);

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }
        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** training_activity_id and training_activity_ids wise filter */
        if (isset($input['training_activity_id'])) {
            $value = $value->where('training_activity_id', $input['training_activity_id']);
        }
        if (isset($input['training_activity_ids']) && count($input['training_activity_ids']) > 0) {
            $value = $value->whereIn('training_activity_id', $input['training_activity_ids']);
        }

        /** training_goal_id and training_goal_ids wise filter */
        if (isset($input['training_goal_id'])) {
            $value = $value->where('training_goal_id', $input['training_goal_id']);
        }
        if (isset($input['training_goal_ids']) && count($input['training_goal_ids']) > 0) {
            $value = $value->whereIn('training_goal_id', $input['training_goal_ids']);
        }

        /** training_intensity_id and training_intensity_ids wise filter */
        if (isset($input['training_intensity_id'])) {
            $value = $value->where('training_intensity_id', $input['training_intensity_id']);
        }
        if (isset($input['training_intensity_ids']) && count($input['training_intensity_ids']) > 0) {
            $value = $value->whereIn('training_intensity_id', $input['training_intensity_ids']);
        }

        if (isset($input['thr'])) {
            $value = $value->where('thr', $input['thr']);
        }

        if (isset($input['is_active'])) {
            $value = $value->where('is_active', $input['is_active']);
        }

        if (isset($input['sequence'])) {
            $value = $value->where('sequence', $input['sequence']);
        }

        /** date wise records filtering*/
        if (isset($input['start_date'])) {
            $value = $value->where('created_at', ">=", $input['start_date']);
        }
        if (isset($input['end_date'])) {
            $value = $value->where('created_at', "<=", $input['end_date']);
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
     * getDetails => Get All details
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
     * getDetailsByInput => Get All Details By Input
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);
        if (isset($input['is_count']) && $input['is_count'] == true) {
            return $value->count();
        }
        $this->getCommonPaginationFilterFn($value, $input);
        return $value;
    }
}
