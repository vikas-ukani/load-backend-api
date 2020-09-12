<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TrainingLog extends Model
{
    /** set table name */
    protected $table = 'training_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',  // user id who created this training log
        'status', // status from constant TRAINING_LOG_STATUS_CARDIO | TRAINING_LOG_STATUS_RESISTANCE
        'date', // training date
        'workout_name', // training title
        'training_goal_id', // training goal id
        'training_goal_custom', // set this key when training goal is customized.
        'training_goal_custom_id',
        'training_intensity_id', // training intensity
        'training_activity_id', // training activity
        'training_log_style_id', // training activity
        'user_own_review', // use can give their own review in digit
        'targeted_hr', // Store Targeted HR
        'notes', // other remark
        'exercise', // store json data
        'RPE', // cycle only
        'is_log', // if true then show log else show workouts only
        'latitude', // log latitude
        'longitude', // log longitude
        'outdoor_route_data', // storing outdoor user activity route data.
        'generated_calculations', // Store Json type after completed training log to summary page
        'comments', // text User Can leave comment here
        'is_complete', // User Complete this training log exercise
    ];
    protected $dates = [
        'date' => 'date_format:Y-m-d H:i:s',
    ];
    protected $casts = [
        'is_complete'           =>  'boolean',
        // 'date' => 'datetime',
    ];

    /**
     * set Validation Rules
     * @param $input
     * @param null $id
     * @return array
     */
    public static function rules($input, $id = null): array
    {
        $once = isset($id) ? 'sometimes|' : '';

        $rules = [
            'status' => $once . 'required',
            'user_id' => $once . 'required',
            'date' => $once . 'required|date_format:Y-m-d H:i:s',
            'workout_name' => $once . 'required',
            // "training_goal_id" => $once. 'required',
            'training_intensity_id' => $once . 'required',
            'exercise' => $once . 'required',
            // "training_activity_id" => 'required',
        ];

        /** status wise validation applied */
        if (isset($input['status']) && $input['status'] === TRAINING_LOG_STATUS_CARDIO) {
            $rules['training_activity_id'] = 'required';
        } /* else if (isset($input['status']) && $input['status'] == TRAINING_LOG_STATUS_CARDIO) {
            $rules = [

                // "training_activity_id" => 'required',
            ];
        }  */
        return $rules;
    }

    /**
     * messages => Set Error Message
     *
     * @return array
     */
    public static function messages(): array
    {
        /** set error message in trans files */
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * validation => Check Validation
     *
     * @param mixed $input
     * @param mixed $id
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($input, self::rules($input, $id), self::messages());
    }

    /**
     * setExerciseAttribute => When store in the database set to sting format
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setExerciseAttribute($value)
    {
        $this->attributes['exercise'] = json_encode($value);
    }

    /**
     * getExerciseAttribute => convert string to object when get data from database
     *
     * @param mixed $value
     *
     * @return void
     */
    public function getExerciseAttribute($value)
    {
        return $this->attributes['exercise'] = json_decode($value, true); // true for get in array form
    }

    // generated_calculations
    public function setGeneratedCalculationsAttribute($value)
    {
        $this->attributes['generated_calculations'] = json_encode($value);
    }
    // generated_calculations
    public function getGeneratedCalculationsAttribute($value)
    {
        return json_decode($value, true); // true for get in array form
        // return $this->attributes['generated_calculations'] = json_decode($value, true); // true for get in array form
    }

    /**
     * setWorkoutNameAttribute => set name to upper word
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setWorkoutNameAttribute($value): void
    {
        $this->attributes['workout_name'] = ucwords(strtolower($value));
    }

    /**
     * scopeOrdered => default sorting on created at as ascending
     *
     * @param mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * user_detail => single user detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user_detail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * training_activity => single activity detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function training_activity()
    {
        return $this->hasOne(TrainingActivity::class, 'id', 'training_activity_id');
    }

    /**
     * training_goal => single goal detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function training_goal()
    {
        return $this->hasOne(TrainingGoal::class, 'id', 'training_goal_id');
    }

    /**
     * training_intensity => single intensity detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function training_intensity()
    {
        return $this->hasOne(TrainingIntensity::class, 'id', 'training_intensity_id');
    }

    public function training_log_style()
    {
        return $this->hasOne(TrainingLogStyle::class, 'id', 'training_log_style_id');
    }

    /**
     * liked_detail => Belong to relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function liked_detail()
    {
        return $this->hasOne(FeedLikes::class, 'feed_id', 'id');
    }
}
