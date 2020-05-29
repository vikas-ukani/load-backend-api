<?php

namespace App\Libraries\Repositories;

use App\Models\LogResistanceValidations;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

class LogResistanceValidationsRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return LogResistanceValidations::class;
    }

    /**
     * @throws \Prettus\Repository\Exceptions\RepositoryException
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
        if (isset($input['search'])) $value = $this->customSearch($value, $input, ['name', 'code', 'is_active']);

        $this->customRelation($value, $input, []);

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }

        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** training_intensity_id wise filter  */
        if (isset($input['training_intensity_id'])) {
            $value = $value->where('training_intensity_id', $input['training_intensity_id']);
        }
        if (isset($input['training_intensity_ids']) && is_array($input['training_intensity_ids']) && count($input['training_intensity_ids'])) {
            $value = $value->whereIn('training_intensity_id', $input['training_intensity_ids']);
        }

        /** training_goal_id wise filter  */
        if (isset($input['training_goal_id'])) {
            $value = $value->where('training_goal_id', $input['training_goal_id']);
        }
        if (isset($input['training_goal_ids']) && is_array($input['training_goal_ids']) && count($input['training_goal_ids'])) {
            $value = $value->whereIn('training_goal_id', $input['training_goal_ids']);
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
            // $value = $value->skip(($input['page'] - 1) * $input['limit'])->take($input['limit']);
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

    /** get details for listing
     * @param null $input
     * @return array
     * @throws \Prettus\Repository\Exceptions\RepositoryException
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
}
