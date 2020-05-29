<?php

namespace App\Libraries\Repositories;

use App\Models\TrainingPrograms;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;
use App\Models\CompletedTrainingProgram;

/**
 * Class CompletedTrainingProgramRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class CompletedTrainingProgramRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return CompletedTrainingProgram::class;
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
            $value = $this->customSearch($value, $input, []);
        }

        /** send relation keys in last param */
        $this->customRelation($value, $input, []);

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }
        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        if (isset($input['title'])) {
            $value = $value->where('title', $input['title']);
        }


        /** program_id and program_ids wise filter */
        if (isset($input['program_id'])) {
            $value = $value->where('program_id', $input['program_id']);
        }
        if (isset($input['program_ids']) && count($input['program_ids']) > 0) {
            $value = $value->whereIn('program_id', $input['program_ids']);
        }

        /** common_programs_weeks_id and common_programs_weeks_ids wise filter */
        if (isset($input['common_programs_weeks_id'])) {
            $value = $value->where('common_programs_weeks_id', $input['common_programs_weeks_id']);
        }
        if (isset($input['common_programs_weeks_ids']) && count($input['common_programs_weeks_ids']) > 0) {
            $value = $value->whereIn('common_programs_weeks_id', $input['common_programs_weeks_ids']);
        }

        if (isset($input['is_complete'])) {
            $value = $value->where('is_complete', $input['is_complete']);
        }

        /** date wise records filtering*/
        if (isset($input['start_date'])) {
            $value = $value->where('date', ">=", $input['start_date']);
        }
        if (isset($input['end_date'])) {
            $value = $value->where('date', "<=", $input['end_date']);
        }
        // if (isset($input['start_date'])) {
        //     $value = $value->where('created_at', ">=", $input['start_date']);
        // }
        // if (isset($input['end_date'])) {
        //     $value = $value->where('created_at', "<=", $input['end_date']);
        // }
        /** get max common id wise record  NOT WORKING*/
        // if (isset($input['is_max_common_programs_weeks_id'])) {
        //     $value = $value->whereMax('common_programs_weeks_id');
        // }
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
