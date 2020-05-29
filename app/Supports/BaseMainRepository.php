<?php

namespace App\Supports;

trait BaseMainRepository
{

    /**
     * customSearch => for main common searching query use anywhere
     *
     * @param mixed $value =>  Main Query Object
     * @param mixed $input => from searching words  where search from
     * @param mixed $searchArray => when search_from is not available then search from back side
     *
     * @return void
     */
    public function customSearch($value, $input, $searchArray = null)
    {
        if (isset($input['search']) && isset($input['search_from']) && count($input['search_from']) > 0) {
            $value = $value->where(function ($q) use ($input) {
                $q = $q->where(array_first($input['search_from']), 'LIKE', '%' . $input['search'] . "%");
                foreach ($input['search_from'] as $key) {
                    $q->orWhere($key, 'LIKE', '%' . $input['search'] . "%");
                }
            });
        } else if (isset($searchArray) && count($searchArray) > 0) {
            /** search where from input search_from key is not sended. */
            $value = $value->where(function ($q) use ($input, $searchArray) {
                $q = $q->where(array_first($searchArray), 'LIKE', '%' . $input['search'] . "%");
                foreach ($searchArray as $key) {
                    $q->orWhere($key, 'LIKE', '%' . $input['search'] . "%");
                }
            });
        }
        return $value;
    }

    /**
     * customRelation => dynamic for custom relation
     *
     * @param  mixed $value => Main Query Object
     * @param  mixed $input => from input get relation key list
     * @param  mixed $relationDetailsArray => all relation keys are here in array
     *
     * @return void
     */
    public function customRelation(&$value, $input, $relationDetailsArray = null)
    {
        if (isset($input['relation']) && count($input['relation']) > 0) {
            foreach ($input['relation'] as $key => $val) {
                /** check for key is integer then select val IMPORTANT */
                $key = is_int($key) ? $val : $key;
                if (is_array($val) && count($val) > 0) {
                    foreach ($val as $k => $v) {
                        /** check for k is integer then select v IMPORTANT */
                        $k = is_int($k) ? $v : $k;
                        $value = $value->with([
                            $key => function ($qq) use ($k, $v) {
                                if (isset($k)) {
                                    $qq = $qq->with([$k]);
                                }
                            }
                        ]);
                    }
                } else {
                    $this->makeRelation($value, $input, $key);
                }
            }
        } else if (isset($relationDetailsArray) && count($relationDetailsArray) > 0) {
            foreach ($relationDetailsArray as $key) {
                $value = $value->with([$key => function ($q) use ($input, $key) {
                    $q = (isset($input[$key . "_list"]))
                        ? $q->select($input[$key . "_list"])
                        : $q->select("*");
                }]);
            }
        }
    }

    /**
     * makeRelation => make relation common
     *
     * @param  mixed $value
     * @param  mixed $input
     * @param  mixed $key
     *
     * @return void
     */
    public function makeRelation(&$value, $input, $key)
    {
        $value = $value->with([
            $key => function ($q) use ($input, $key) {
                $q = (isset($input[$key . "_list"]))
                    ? $q->select($input[$key . "_list"])
                    : $q->select("*");
                if (isset($input[$key . "_where"]) && count($input[$key . "_where"]) > 0) {
                    foreach ($input[$key . "_where"] as $whkey => $whValue) {
                        $q = $q->where($whkey, $whValue);
                    }
                }
            }
        ]);
    }

    /**
     * customPaginate => Create Common custom pagination
     *
     * @param  mixed $value => Main Query Object
     * @param  mixed $input => for page and limit from input
     *
     * @return void
     */
    public function customPaginate($value, $input)
    {
        $limit = (((int) $input['limit']) * 1);
        $start = (((int) $input['page']) * 1);
        return $value->skip(($start - 1) * $limit)->take($limit);
    }
}
