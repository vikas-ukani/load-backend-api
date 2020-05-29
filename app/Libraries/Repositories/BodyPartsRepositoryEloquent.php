<?php

namespace App\Libraries\Repositories;

use App\Models\BodyParts;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

/**
 * Class UsersRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class BodyPartsRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return BodyParts::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function getDetails($input = null)
    {
        $value = $this->makeModel();

        /** searching */
        if (isset($input['search'])) {
            $value = $this->customSearch($value, $input, ['name', 'code', 'is_active',]);
        }

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }

        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
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
        /** send relation keys in last param */
        $this->customRelation($value, $input, []);

        /** date wise records */
        if (isset($input['start_date'])) {
            $value = $value->where('created_at', ">=", $input['start_date']);
        }

        $count = $value->count();

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

        // return $value;

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
     * @param null $input
     * @return mixed
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();

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
        if (isset($input['is_active'])) {
            $value = $value->where('is_active', $input['is_active']);
        }
        /** send relation keys in last param */
        $this->customRelation($value, $input, []);

        // if (isset($input['display_at']) && $input['display_at'] == true) {
        //     $value = $value->where('display_at', $input['display_at']);
        // }

        if (isset($input['display_at'])) {
            $value = $value->whereRaw("find_in_set('" . $input['display_at'] . "',display_at)");
        }

        if (isset($input['is_display_at_exist']) && $input['is_display_at_exist'] == true) {
            $value = $value->whereNotNull('display_at');
        }

        /** date wise records */
        if (isset($input['start_date'])) {
            $value = $value->where('created_at', ">=", $input['start_date']);
        }

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
        return $value;
    }
}
