<?php

namespace App\Libraries\Repositories;

use App\Models\LoadCenterEvent;
use App\Supports\BaseMainRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Libraries\RepositoriesInterfaces\UsersRepository;

class LoadCenterEventRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return LoadCenterEvent::class;
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

        //  user_detail,
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

        /** user_id wise filter  */
        if (isset($input['user_id'])) {
            $value = $value->where('user_id', $input['user_id']);
        }
        if (isset($input['user_ids']) && is_array($input['user_ids']) && count($input['user_ids'])) {
            $value = $value->whereIn('user_id', $input['user_ids']);
        }

        /** filter */
        if (isset($input['visible_to'])) {
            $value = $value->where('visible_to', $input['visible_to']);
        }
        if (isset($input['event_name'])) {
            $value = $value->where('event_name', $input['event_name']);
        }
        if (isset($input['event_price'])) {
            $value = $value->where('event_price', $input['event_price']);
        }
        if (isset($input['is_completed'])) {
            $value = $value->where('is_completed', $input['is_completed']);
        }
        /** date wise records */
        if (isset($input['start_date'])) {
            $value = $value->where('date', ">=", $input['start_date']);
        }

        if (isset($input['is_nearest_data']) && isset($input['location_map'])) {
            $value = $value->select(
                "load_center_events.id",
                \DB::raw("6371 * acos(cos(radians(" . $input['location_map']['lat'] . "))
                * cos(radians(load_center_events.location_map.lat))
                * cos(radians(load_center_events.location_map.long) - radians(" . $input['location_map']['long'] . "))
                + sin(radians(" .  $input['location_map']['lat'] . "))
                * sin(radians(load_center_events.lat))) AS distance")
            )->groupBy("load_center_events.id");

            // $value = $value->where('is_nearest_data', $input['is_nearest_data']);
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
     * getDetailsByInput => get details by input
     *
     * @param  mixed $input
     *
     * @return void
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
     * getNearestArea => get nearest location wise get events
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function getNearestArea($input = null)
    {
        $lat = $input['latitude'] ?? 0;
        $lon = $input['longitude'] ?? 0;
        $distance = 1000;

        $value = $this->makeModel();
        return $value->select('*', \DB::raw(sprintf(
            '(6371 * acos(cos(radians(%1$.7f)) * cos(radians(latitude)) * cos(radians(longitude)
                 - radians(%2$.7f)) + sin(radians(%1$.7f)) * sin(radians(latitude)))
                 ) AS distance',
            $lat,
            $lon
        )))->orderBy('distance', 'asc')->limit(10)->get(); //->having('distance', '<', $distance)
    }
}
