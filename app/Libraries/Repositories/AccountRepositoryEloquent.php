<?php

namespace App\Libraries\Repositories;

use App\Libraries\RepositoriesInterfaces\UsersRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;
use App\Supports\BaseMainRepository;
use App\Models\Accounts;

/**
 * Class UsersRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class AccountRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Accounts::class;
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
            $value = $this->customSearch($value, $input, ['name', 'code', 'is_active', 'free_trial_days']);
        }

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }

        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        if (isset($input['name'])) {
            $value = $value->whereName($input['name']);
        }

        if (isset($input['code'])) {
            $value = $value->whereCode($input['code']);
        }
        if (isset($input['codes']) && is_array($input['codes']) && count($input['codes'])) {
            $value = $value->whereIn('code', $input['codes']);
        }

        $this->customRelation($value, $input, []);

        /** date wise records */
        if (isset($input['start_date'])) {
            $value = $value->where('created_at', ">=", $input['start_date']);
        }

        /** check for account is active or not */
        if (isset($input['is_active'])) {
            $value = $value->where('is_active', $input['is_active']);
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

        if (isset($input['first']) && $input['first'] === true) {
            $value = $value->first();
        } elseif (isset($input['is_deleted']) && $input['is_deleted'] === true) {
            $value = $value->withTrashed()->get();
        } else {
            $value = $value->get();
        }
    }

    /**
     * getDetails => get details for list
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
     * getDetailsByInput => get details by input
     *
     * @param  mixed $input
     *
     * @return object
     */
    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();
        $this->commonFilterFn($value, $input);
        $this->getCommonPaginationFilterFn($value, $input);
        return $value;
    }
}
