<?php /** @noinspection ALL */

namespace App\Models;

use App\Supports\DateConvertor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class LoadCenterRequest extends Model
{
    use DateConvertor;

    protected $table = "load_center_requests";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', // who create this request
        // 1 screen
        'title', // title of request
        'start_date', // start date of trining
        'birth_date', // Birth date
        'yourself', // describe your self
        // 2
        'specialization_ids',  // multiple ids from specialization
        'training_type_ids', // multiple training types
        'experience_year', // year of experience
        'country_id', // country select
        'rating', // describe your self
        // 3
    ];

    /**
     * rules => set validation rules
     *
     * @param mixed $id
     *
     * @return array
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';

        return [
            // 1.
            'title' => $once . 'required|max:200',
            'user_id' => $once . 'required|integer',
            'start_date' => $once . 'required',
            // 2.
        ];
    }

    /**
     * messages => Set Error Message
     *
     * @return array
     */
    public static function messages()
    {
        return [
            // 'required' => __('validation.required'),
        ];
    }

    /**
     * validation => Check Validation
     *
     * @param  mixed $input
     * @param  mixed $id
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, LoadCenterRequest::rules($id), LoadCenterRequest::messages());
    }

    /**
     * setTitleAttribute => set title to capital first
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = ucwords(strtolower($value));
    }

    /**
     * setStartDateAttribute => convert to iso to UTC format
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $this->isoToUTCFormat($value);
    }

    /**
     * setStartDateAttribute => convert to iso to UTC formate
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setBirthDateAttribute($value)
    {
        $this->attributes['birth_date'] = $this->isoToUTCFormat($value);
    }

    /**
     * setTrainingTypeIdsAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setTrainingTypeIdsAttribute($value)
    {
        $this->attributes['training_type_ids'] = implode(',', $value);
    }

    /**
     * getTrainingTypeIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return array
     */
    public function getTrainingTypeIdsAttribute($value)
    {
        return $this->attributes['training_type_ids'] = isset($value) ?  explode(',', $value) : null;
    }

    /**
     * setSpecializationIdsAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setSpecializationIdsAttribute($value)
    {
        $this->attributes['specialization_ids'] = implode(',', $value);
    }

    /**
     * getSpecializationIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return array
     */
    public function getSpecializationIdsAttribute($value)
    {
        return $this->attributes['specialization_ids'] = isset($value) ?  explode(',', $value) : null;
    }

    /**
     * scopeOrdered =>default sorting on created at as ascending
     *
     * @param  mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * user_detail => relation with user details
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user_detail()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    /** Relation with training types details
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function training_type_details()
    {
        return $this->hasOne(TrainingTypes::class, 'id', 'user_id');
    }
}
