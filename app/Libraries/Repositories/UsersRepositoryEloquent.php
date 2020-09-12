<?php

namespace App\Libraries\Repositories;

use App\Models\User;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;
use Illuminate\Support\Facades\Auth;

class UsersRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return User::class;
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
            $value = $this->customSearch($value, $input, ['name', 'email', 'mobile']);
        }

        /** except current login user */
        if (isset($input['is_except_current_user']) && $input['is_except_current_user'] == true) {
            $value = $value->where('id', '<>', Auth::id());
        }

        /** filter by account id */
        if (isset($input['account_id'])) {
            $value = $value->where('account_id', $input['account_id']);
        }
        if (isset($input['account_ids']) && is_array($input['account_ids']) && count($input['account_ids'])) {
            $value = $value->whereIn('account_id', $input['account_ids']);
        }

        /** get users from id */
        if (isset($input['user_id'])) {
            $value = $value->whereUserId($input['user_id']);
        }
        /** filter by id  */
        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }
        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }

        if (isset($input['name'])) {
            $value = $value->whereName($input['name']);
        }

        if (isset($input['email'])) {
            $value = $value->whereEmail($input['email']);
        }

        $this->customRelation($value, $input, []); //'account_detail'

        /** gender and genders wise filter */
        if (isset($input['gender'])) {
            $value = $value->where('gender', $input['gender']);
        }
        if (isset($input['genders']) && is_array($input['genders']) && count($input['genders'])) {
            $value = $value->whereIn('gender', $input['genders']);
        }

        /** check user type */
        if (isset($input['user_type'])) {
            $value = $value->where('user_type', $input['user_type']);
        }
        if (isset($input['user_types']) && is_array($input['user_types']) && count($input['user_types'])) {
            $value = $value->whereIn('user_type', $input['user_types']);
        }

        /** check last login where user login */
        if (isset($input['last_login_at'])) {
            $value = $value->where('last_login_at', '<=', $input['last_login_at']);
        }

        /** check last login is have null  */
        if (isset($input['is_last_login']) && $input['is_last_login'] == false) {
            $value = $value->orWhereNull('last_login_at');
        }

        /** date wise records */
        if (isset($input['start_date'])) {
            $value = $value->where('created_at', ">=", $input['start_date']);
        }

        /** check for user active or not */
        if (isset($input['is_active'])) {
            $value = $value->where('is_active', $input['is_active']);
        }

        /** check if false then don't show current user in listing */
        if (isset($input['is_current_user']) && $input['is_current_user'] == false) {
            $value = $value->where('id', '<>', \Auth::id());
        }

        if (isset($input['facebook'])) {
            $value = $value->where('facebook', $input['facebook']);
        }

        /** country_id and country_ids wise filter */
        if (isset($input['country_id'])) {
            $value = $value->where('country_id', $input['country_id']);
        }
        if (isset($input['country_ids']) && is_array($input['country_ids']) && count($input['country_ids'])) {
            $value = $value->whereIn('country_id', $input['country_ids']);
        }

        if (isset($input['latitude'])) {
            $value = $value->where('latitude', $input['latitude']);
        }
        if (isset($input['longitude'])) {
            $value = $value->where('longitude', $input['longitude']);
        }


        if (isset($input['is_snooze'])) {
            $value = $value->where('is_snooze', $input['is_snooze']);
        }

        /** check for user complete their profile or not */
        if (isset($input['is_profile_complete'])) {
            $value = $value->where('is_profile_complete', $input['is_profile_complete']);
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

    /**
     * checkKeysExist => Check key exists in db or not - RESPONSE BOOLEAN
     *
     * @param  mixed $key
     * @param  mixed $input
     *
     * @return void
     */
    public function checkKeysExist($key, $input)
    {
        $value = $this->makeModel();

        $value = $value->where($key, $input[$key]);
        if ($value->first()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getRecords => get records by input
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getRecords($input)
    {
        $value = $this->makeModel();

        if (isset($input['email'])) {
            $value = $value->whereEmail($input['email']);
        }

        if (isset($input['first'])) {
            $value = $value->first();
        } else {
            $value = $value->get();
        }
        return $value;
    }

    /**
     * checkEmailExists => check for email is exists or not
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function checkEmailExists($input = null)
    {
        $value = $this->makeModel();
        $value = $value->whereEmail($input['email']);
        $value = $value->first();
        return $value;
    }

    /**
     * checkEmailRecordDeleted => check records for is deleted or not
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function checkEmailRecordDeleted($input = null)
    {
        $value = $this->makeModel();
        $value = $value->whereEmail($input['email']);
        $value = $value->withTrashed()->first();
        return $value;
    }

    /**
     * getUserCountByType => get user  count by their types
     *
     * @return void
     */
    public function getUserCountByType()
    {
        $value = $this->makeModel();






        return $value;
    }
}
