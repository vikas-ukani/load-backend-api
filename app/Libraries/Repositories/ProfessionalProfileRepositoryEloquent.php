<?php

namespace App\Libraries\Repositories;

use App\Models\User;
use App\Models\ProfessionalProfile;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

class ProfessionalProfileRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ProfessionalProfile::class;
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
            $value = $this->customSearch($value, $input, []);
        }
        $this->customRelation($value, $input, []);
        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }

        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }
        /** specialization_id and specialization_ids wise filter */
        if (isset($input['specialization_id'])) {
            // dd("check data", $input['specialization_id']);
            $value = $value->whereRaw("find_in_set(" . $input['specialization_id'] .  ",specialization_ids)");
        }
        if (isset($input['specialization_ids']) && count($input['specialization_ids']) > 0) {
            /** FIXME  Not Working IN */
            // $value = $value->whereRawIn("find_in_set(" . $input['specialization_ids'] .  ",specialization_ids)");
            // $value = $value->whereIn('specialization_id', $input['specialization_ids']);
        }
        /** user_id and user_ids wise filter */
        if (isset($input['user_id'])) {
            $value = $value->where('user_id', $input['user_id']);
        }
        if (isset($input['user_ids']) && is_array($input['user_ids']) && count($input['user_ids'])) {
            $value = $value->whereIn('user_id', $input['user_ids']);
        }

        /** check where not in user ids */
        if (isset($input['expect_user_ids'])) {
            // dd('check whee not in user ids', $input['expect_user_ids']);
            $value = $value->whereNotIn('user_id', $input['expect_user_ids']);
        }

        if (isset($input['introduction'])) {
            $value = $value->where('introduction', $input['introduction']);
        }

        if (isset($input['is_active'])) {
            $value = $value->where('is_active', $input['is_active']);
        }

        if (isset($input['language_ids']) && is_array($input['language_ids']) && count($input['language_ids'])) {
            $value = $value->where(function ($query) use ($input) {
                $query = $query->where('languages_spoken_ids', "LIKE", "%" . implode(',', $input['language_ids']) . "%")
                    ->orWhere('languages_written_ids', "LIKE", "%" . implode(',', $input['language_ids']) . "%");
                // $query = $query->whereInRaw("find_in_set(" . $input['language_ids'] .  ",languages_spoken)")
                //     ->orWhereInRaw("find_in_set(" . $input['language_ids'] .  ",languages_written_ids)");
            });
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
        if (isset($input['nearest_limit'])) {
            $value = $value->limit($input['nearest_limit']);
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
     * getDetailsByInput =>get detail by input
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
     * getNearestAreaProfiles => get nearest location wise get events
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getNearestAreaProfiles($input = null)
    {
        $lat = $input['latitude'] ?? 0;
        $lon = $input['longitude'] ?? 0;
        $distance = 1000;

        // $distance_select = sprintf(
        //     "( %d * acos( cos( radians(%s) ) " .
        //         " * cos( radians( `latitude` ) ) " .
        //         " * cos( radians( `longitude` ) - radians(%s) ) " .
        //         " + sin( radians(%s) ) * sin( radians( `latitude` ) ) " .
        //         " ) " .
        //         ")",
        //     6371,
        //     $lat,
        //     $lon,
        //     $lat
        // );
        // return  User::select('id')
        //     ->having(\DB::raw($distance_select), '<=', $distance)
        //     ->groupBy('users.id')->paginate(1);

        $value = new User();
        $value = $value->select('id', \DB::raw(sprintf(
            '(6371 * acos(cos(radians(%1$.7f)) * cos(radians(latitude)) * cos(radians(longitude)
                 - radians(%2$.7f)) + sin(radians(%1$.7f)) * sin(radians(latitude)))
                 ) AS `distance`',
            $lat,
            $lon
        )));
        $value = $value->orderBy('distance', 'asc');
        // $value = $value->having('distance', '<', $distance); // In  Server db not working showing error .
        // $value = $value->having('distance', '<', $distance);
        $value =  $value->limit(10);
        $value = $value->get();
        return $value;
    }
}
