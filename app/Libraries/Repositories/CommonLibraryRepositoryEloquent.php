<?php

namespace App\Libraries\Repositories;

use App\Models\CommonLibrary;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

class CommonLibraryRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return CommonLibrary::class;
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
            $value = $this->customSearch($value, $input, ['exercise_name']);
        }

        $this->customRelation($value, $input, []);

        if (isset($input['exercise_name'])) {
            $value = $value->where('exercise_name', $input['exercise_name']);
        }

        /** id wise filter  */
        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }
        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        /** category_id and category_ids wise filter */
        if (isset($input['category_id'])) {
            $value = $value->where('category_id', $input['category_id']);
        }
        if (isset($input['category_ids']) && count($input['category_ids']) > 0) {
            $value = $value->whereIn('category_id', $input['category_ids']);
        }

        /** region_id wise filter  */
        if (isset($input['regions_id'])) {
            $value = $value->where('regions_ids', $input['regions_id']);
        }
        if (isset($input['regions_ids']) && is_array($input['regions_ids']) && count($input['regions_ids'])) {
            $value = $value->whereIn('regions_ids', $input['regions_ids']);
        }

        /** body_part_id wise filter  */
        if (isset($input['body_part_id'])) {
            $value = $value->where('body_part_id', $input['body_part_id']);
        }
        if (isset($input['body_part_ids']) && is_array($input['body_part_ids']) && count($input['body_part_ids'])) {
            $value = $value->whereIn('body_part_id', $input['body_part_ids']);
        }

        /** mechanics_id wise filter  */
        if (isset($input['mechanics_id'])) {
            $value = $value->where('mechanics_id', $input['mechanics_id']);
        }
        if (isset($input['mechanics_ids']) && is_array($input['mechanics_ids']) && count($input['mechanics_ids'])) {
            $value = $value->whereIn('mechanics_id', $input['mechanics_ids']);
        }

        /** targeted_muscles_id wise filter  */
        if (isset($input['targeted_muscles_id'])) {
            $value = $value->where('targeted_muscles_id', $input['targeted_muscles_id']);
        }
        if (isset($input['targeted_muscles_ids']) && is_array($input['targeted_muscles_ids']) && count($input['targeted_muscles_ids'])) {
            $value = $value->whereIn('targeted_muscles_id', $input['targeted_muscles_ids']);
        }

        /** action_force_id wise filter  */
        if (isset($input['action_force_id'])) {
            $value = $value->where('action_force_id', $input['action_force_id']);
        }
        if (isset($input['action_force_ids']) && is_array($input['action_force_ids']) && count($input['action_force_ids'])) {
            $value = $value->whereIn('action_force_id', $input['action_force_ids']);
        }

        /** equipment_id wise filter  */
        if (isset($input['equipment_id'])) {
            $value = $value->where('equipment_id', $input['equipment_id']);
        }
        if (isset($input['equipment_ids']) && is_array($input['equipment_ids']) && count($input['equipment_ids'])) {
            $value = $value->whereIn('equipment_id', $input['equipment_ids']);
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

    /**
     * getDetails => get details for listing
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

    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);
        $this->getCommonPaginationFilterFn($value, $input);
        return $value;
    }
}
