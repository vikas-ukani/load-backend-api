<?php

namespace App\Models;

use App\Supports\DateConvertor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Validator;

class ProfessionalProfile extends Model
{
    use DateConvertor;

    protected $table = "setting_professional_profiles";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // 1 screen 
        'user_id', // User Profile id from user table
        'profession', // title of event
        'location_name', // Location Name
        'introduction', // title of event
        'specialization_ids', // user specialization
        'rate', // training rate in number
        'currency_id', // currency id 
        'general_rules', // currency id 
        'academic_credentials', // academic and certification details
        // 'academic_credentials', // academic and certification details
        'experience_and_achievements', // Experience and achievements
        'terms_of_service', // Terms of service
        'languages_spoken_ids', // Language are spoken languages ids
        'languages_written_ids', // Language are written languages ids

        //// Session Related Details
        'session_duration', // Session duration in mins
        // 'session_types', // Session types  
        'professional_type_id', // Session Types ( From Professional Types table )  
        'session_maximum_clients', // maximum client in number(s)

        'basic_requirement', // Basics Requirement
        'is_forms', // is forms true or false
        'is_answerd', // to get is answerd from question form(s)
        ////  Information
        'amenities', //  providing amenities

        ////  Cancellation policies
        'cancellation_policy_id', // currency id  

        //// Payment AND Rates
        'payment_option_id', // relation with payment options
        'per_session_rate', // single session rate
        'per_multiple_session_rate', // multiple session rate

        //// Availability
        'days', // Profile Availability
        'is_auto_accept', // check for auto accept is true OR false

    ];
    protected $appends = ['is_bookmarked'];

    /**
     * validation => Check Validation
     *
     * @param  mixed $input
     * @param  mixed $id
     *
     * @return void
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, ProfessionalProfile::rules($id), ProfessionalProfile::messages());
    }

    /**
     * rules => set validation rules
     *
     * @param  mixed $id
     *
     * @return void
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';
        $rules = [
            // 1. 
            'user_id' => $once . 'required|integer',
            'profession' => $once . 'required',
            'introduction' => $once . 'required',
            'specialization_ids' => $once . 'required|array|max:3',
            'academic_credentials' => $once . 'required',
            'experience_and_achievements' => $once . 'required',
            'rate' => $once . 'required|regex:/^\d+(\.\d{1,2})?$/', // training rate in real number
            'currency_id' => $once . 'required', // training rate in real number
            'cancellation_policy_id' => $once . 'required', // training rate in real number
            'currency_id' => $once . 'required', // training rate in real number
        ];
        return $rules;
    }

    /**
     * messages => Set Error Message
     *
     * @return void
     */
    public static function messages()
    {
        return [
            // 'required'=> __('validation.required'),
        ];
    }

    /**
     * setDateTimeAttribute => set title to capital first
     *
     * @param  mixed $value
     *
     * @return void
     */
    // public function setDateTimeAttribute($value)
    // {
    //     $this->attributes['date_time'] = $this->isoToUTCFormat($value);
    // }

    /**
     * setIntroductionAttribute => set introduction to capital first
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setIntroductionAttribute($value)
    {
        $this->attributes['introduction'] = ucwords(strtolower($value));
    }

    /**
     * setDaysAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setDaysAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['days'] =  $value;
    }

    /**
     * getDaysAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getDaysAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['days'] = $value;
    }

    /**
     * setLanguagesSpokenAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setLanguagesSpokenIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['languages_spoken_ids'] =  $value;
    }

    /**
     * getSpecializationIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getLanguagesSpokenIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['languages_spoken_ids'] = $value;
    }

    /**
     * setLanguagesWrittenAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setLanguagesWrittenIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['languages_written_ids'] = $value;
    }

    /**
     * getSpecializationIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getLanguagesWrittenIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['languages_written_ids'] = $value;
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
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['specialization_ids'] = $value;
    }

    /**
     * getSpecializationIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getSpecializationIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['specialization_ids'] = $value;
    }

    /**
     * setAmenitiesAttribute => convert to json
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setAmenitiesAttribute($value)
    {
        $value = is_string($value) ? $value : json_encode($value);
        $this->attributes['amenities'] = $value;
    }

    /**
     * getAmenitiesAttribute => json to real
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getAmenitiesAttribute($value)
    {
        return $this->attributes['amenities'] = is_string($value) ? json_decode($value, true) : $value;
    }

    /** academic_credentials
     * setAcademicCredentialsAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setAcademicCredentialsAttribute($value)
    {
        $value = is_string($value) ? $value : json_encode($value);
        $this->attributes['academic_credentials'] = $value;
    }

    /**
     * getAcademicCredentialsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getAcademicCredentialsAttribute($value)
    {
        return $this->attributes['academic_credentials'] = is_string($value) ? json_decode($value, true) : $value;
    }

    /**
     * getIsAnswerdAttribute
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getIsAnswerdAttribute($value)
    {
        if ($value === 1) $value = true;
        elseif ($value === 0) $value = false;
        else $value = null;
        return $this->attributes['is_answerd'] = $value;
    }

    /**
     * getIsFormsAttribute
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getIsFormsAttribute($value)
    {
        return $this->attributes['is_forms'] = ($value === 1) ? true : false;
    }

    /**
     * getIsAutoAcceptAttribute
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getIsAutoAcceptAttribute($value)
    {
        return $this->attributes['is_auto_accept'] = ($value === 1) ? true : false;
    }

    public function getIsBookmarkedAttribute()
    {
        return !!Bookmark::where('professional_id', $this->attributes['id'])->where('user_id', Auth::id())->count();
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
    public function scopeCustomWhereIn($query)
    {
        // $query->whereRaw("find_in_set('php',tags)");
        // dd('che quest', $query);
        // return $query->whereIn()
    }

    /**
     * user_detail => relation with user details
     *
     * @return void
     */
    public function user_detail()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    /**
     * specialization_details => Specialization Details
     *
     * @return void
     */
    public function specialization_details()
    {
        return $this->hasMany(UsersRelations::class, 'user_id', 'id');
    }

    /**
     * currency_detail => get currency details
     *
     * @return void
     */
    public function currency_detail()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function professional_type_detail()
    {
        return $this->hasOne(ProfessionalTypes::class, 'id', 'professional_type_id');
    }

    /**
     * cancellation_policy_detail => relation with cancellation policy
     *
     * @return void
     */
    public function cancellation_policy_detail()
    {
        return $this->hasOne(CancellationPolicy::class, 'id', 'cancellation_policy_id');
    }

    /**
     * payment_option_detail => Payment Option Relation
     *
     * @return void
     */
    public function payment_option_detail()
    {
        return $this->hasOne(PaymentOptions::class, 'id', 'payment_option_id');
    }
}
