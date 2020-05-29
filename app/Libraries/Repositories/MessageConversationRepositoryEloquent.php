<?php

namespace App\Libraries\Repositories;

use App\Libraries\RepositoriesInterfaces\UsersRepository;
use App\Models\MessageConversation;
use App\Supports\BaseMainRepository;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class MessageConversationRepositoryEloquent.
 *
 * @package namespace App\Libraries\Repositories;
 */
class MessageConversationRepositoryEloquent extends BaseRepository implements UsersRepository
{
    use BaseMainRepository;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return MessageConversation::class;
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
     * getDetails => Get Listing With Conditions wise
     *
     * @param mixed $input
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
        if (isset($input['search'])) {
            $value = $this->customSearch($value, $input, []);
        }

        if (isset($input['id'])) {
            $value = $value->where('id', $input['id']);
        }
        if (isset($input['ids']) && is_array($input['ids']) && count($input['ids'])) {
            $value = $value->whereIn('id', $input['ids']);
        }
        /** from_id and from_ids wise filter */
        if (isset($input['from_id'])) {
            $value = $value->where('from_id', $input['from_id']);
        }
        if (isset($input['from_ids']) && count($input['from_ids']) > 0) {
            $value = $value->whereIn('from_id', $input['from_ids']);
        }

        /** to_id and to_ids wise filter */
        if (isset($input['to_id'])) {
            $value = $value->where('to_id', $input['to_id']);
        }
        if (isset($input['to_ids']) && count($input['to_ids']) > 0) {
            $value = $value->whereIn('to_id', $input['to_ids']);
        }

        /** training_log_id and training_log_ids wise filter */
        if (isset($input['training_log_id'])) {
            $value = $value->where('training_log_id', $input['training_log_id']);
        }
        if (isset($input['training_log_ids']) && count($input['training_log_ids']) > 0) {
            $value = $value->whereIn('training_log_id', $input['training_log_ids']);
        }

        /** event_id and event_ids wise filter */
        if (isset($input['event_id'])) {
            $value = $value->where('event_id', $input['event_id']);
        }
        if (isset($input['event_ids']) && count($input['event_ids']) > 0) {
            $value = $value->whereIn('event_id', $input['event_ids']);
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
    }

    /**
     * updateRich => update some keys
     *
     * @param mixed $input => updated input
     * @param mixed $id => update id record
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
     * getDetailsByInput => get pagination and get data query
     *
     * @param mixed $input
     *
     * @return Model
     */
    public function getDetailsByInput($input = null)
    {
        $value = $this->makeModel();

        /** common filter applied here */
        $this->commonFilterFn($value, $input);

        /** get pagination filter get data */
        $this->getCommonPaginationFilterFn($value, $input);

        return $value;
    }

    public function getDetailsByFromOrTo($input = null)
    {
        if (isset($input)) {
            $value = $this->makeModel();

            $value = $value->where(function ($query) use ($input) {
                $query = $query->where('from_id', $input['from_id'])
                    ->orWhere('to_id', $input['from_id']);
            });

            $value = $value->where(function ($query) use ($input) {
                $query = $query->where('from_id', $input['to_id'])
                    ->orWhere('to_id', $input['to_id']);
            });

//            $value = $value->where(function ($query) use ($input) {
//                $query = $query->where('from_id', $input['from_id'])
//                    ->orWhere('to_id', $input['to_id'])
//                    ->orWhere('from_id', $input['to_id'])
//                    ->orWhere('to_id', $input['from_id']);
//            });

            if (isset($input['relation']) && is_array($input['relation'])) {
                $this->customRelation($value, $input, []);
            }

            return $value->first();
        } else {
            return null;
        }
    }
}
