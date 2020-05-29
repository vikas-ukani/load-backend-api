<?php

namespace App\Libraries\Repositories;

use App\Models\TrainingPrograms;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

/**
 * Class UsersRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class TrainingProgramsRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return TrainingPrograms::class;
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

        if (isset($input['status'])) {
            $value = $value->where('status', $input['status']);
        }
        if (isset($input['type'])) {
            $value = $value->where('type', $input['type']);
        }

        /** user id wise filter  */
        if (isset($input['user_id'])) {
            $value = $value->where('user_id', $input['user_id']);
        }
        if (isset($input['user_ids']) && is_array($input['user_ids']) && count($input['user_ids'])) {
            $value = $value->whereIn('user_id', $input['user_ids']);
        }

        /** preset_training_programs_id wise filter  */
        if (isset($input['preset_training_programs_id'])) {
            $value = $value->where('preset_training_programs_id', $input['preset_training_programs_id']);
        }
        if (isset($input['preset_training_programs_ids']) && is_array($input['preset_training_programs_ids']) && count($input['preset_training_programs_ids'])) {
            $value = $value->whereIn('preset_training_programs_id', $input['preset_training_programs_ids']);
        }

        /** training_frequencies_id wise filter  */
        if (isset($input['training_frequencies_id'])) {
            $value = $value->where('training_frequencies_id', $input['training_frequencies_id']);
        }
        if (isset($input['training_frequencies_ids']) && is_array($input['training_frequencies_ids']) && count($input['training_frequencies_ids'])) {
            $value = $value->whereIn('training_frequencies_id', $input['training_frequencies_ids']);
        }

        /** training_activity_id wise filter  */
        if (isset($input['training_activity_id'])) {
            $value = $value->where('training_activity_id', $input['training_activity_id']);
        }
        if (isset($input['training_activity_ids']) && is_array($input['training_activity_ids']) && count($input['training_activity_ids'])) {
            $value = $value->whereIn('training_activity_id', $input['training_activity_ids']);
        }

        /** date wise records filtering*/
        // if (isset($input['start_date'])) {
        //     $value = $value->where('date', ">=", $input['start_date']);
        // }
        // if (isset($input['end_date'])) {
        //     $value = $value->where('date', "<=", $input['end_date']);
        // }

        // if (isset($input['current_month'])) {
        //     // ->whereRaw('MONTH(created_at) = ?',[$currentMonth])
        //     $value = $value->where('currenst_month', $input['current_month']);
        // }

        /** date wise records filtering*/
        if (isset($input['start_date'])) {
            $value = $value->where('end_date', ">=", $input['start_date']);
        }
        if (isset($input['end_date'])) {
            $value = $value->where('start_date', "<=", $input['end_date']);
        }
        // if (isset($input['end_date'])) {
        //     $value = $value->where('end_date', "<=", $input['end_date']);
        // }
        /** find from string bcz in db store in string from array */
        if (isset($input['days'])) {
            $value = $value->where('days', 'LIKE', "%" . $input['days'] . "%");
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
