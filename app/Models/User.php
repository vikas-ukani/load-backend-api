<?php

namespace App\Models;

use App\Supports\DateConvertor;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, Notifiable, DateConvertor;

    protected $table = "users";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', // user name
        'email',  // user verified name
        'password', // user password
        'country_code',   // Country code for mobile verification
        'mobile',   // user mobile
        'facebook',   // user facebook id
        'date_of_birth',  // user date_of_birth
        'gender',  // user gender
        'height', // user height
        'weight', // user wright
        'photo', // user profile pic
        'goal', // user goal
        'country_id',  //  user location
        'latitude', // user latitude
        'longitude', // user longitude
        'membership_code', // when user to be a member store membership code unique
        'user_type',
        'account_id', // default free account
        'is_active', // active deactivate user  default true
        'is_profile_complete', // active deactivate user  default true
        'email_verified_at', // date when user verify that email  // default null
        'mobile_verified_at',  // date when user verify that mobile  // default null
        'expired_at', // account valid expired time
        'last_login_at', // set last login date time in utc date
        'is_snooze', // for professional user is snooze or not
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * rules => set Validation Rules
     *
     * @param  mixed $id
     *
     * @return array
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';

        // if (isset($id)) {
        //     // $rules['email'] = "sometimes|" . $rules['email'] . ",id,{$id}";
        //     $rules['password'] = "sometimes|" . $rules['password'];
        // }
        return [
            'name' => $once . 'required|max:100',
            'email' => $once . "required|email|unique:users,email,{$id}",
            'password' => $once . 'required',
            'role' => $once . 'required',
            'country_code' => $once . 'required',
            'mobile' => 'required',
            'is_snooze' => $once . 'boolean',
            // 'phone' => $once . 'required|digits_between:10,11',
        ];
    }

    /**
     * messages => Set Error Message
     *
     * @return array
     */
    public static function messages()
    {
        /** set error message in trans files */
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * validation => **
     *
     *
     * @param  mixed $input
     * @param  mixed $id
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, User::rules($id), User::messages());
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * setNameAttribute => tittle case
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords(strtolower($value));
    }

    /**
     * setEmailAttribute => convert email to lower always
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    /**
     * getPhotoAttribute => append base url to image with unique
     *
     * @param  mixed $value
     *
     * @return string
     */
    public function getPhotoAttribute($value)
    {
        $this->attributes['photo'] = env('APP_URL',  url('/')) . $value;
        $arr = array_unique(explode(env('APP_URL',  url('/')), $this->attributes['photo']));
        return $this->attributes['photo'] = implode(env('APP_URL',  url('/')), $arr);
    }


    /**
     * getEmailVerifiedAtAttribute => for verifying email
     *
     * @param  mixed $value
     *
     * @return void
     */
    // public function getEmailVerifiedAtAttribute($value)
    // {
    //     return $this->attributes[ 'email_verified_at'] = $value ?? NULL;
    // }
    // public function getMobileVerifiedAtAttribute($value)
    // {
    //     return $this->attributes[ 'mobile_verified_at'] = $value ?? NULL;
    // }


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * scopeOrdered => default sorting on created at as ascending
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
     * account_detail => relation with account that user have
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account_detail()
    {
        return $this->hasOne("App\Models\Accounts", 'id', "account_id");
    }

    /**
     * country_detail => relation with user country
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function country_detail()
    {
        return $this->hasOne(Countries::class, 'id', 'country_id');
    }

    /**
     * profile_detail => get professional user detail using belongs to relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function profile_detail()
    {
        return $this->belongsTo(ProfessionalProfile::class, 'id', 'user_id');
    }

    /**
     * user_snooze_detail => get user snooze relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user_snooze_detail()
    {
        return $this->hasOne(UsersSnooze::class, 'user_id', 'id');
    }
}
