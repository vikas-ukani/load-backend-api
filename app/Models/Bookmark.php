<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Bookmark extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', // title name
        'event_id', // from title 
        'professional_id', // active or not 
    ];

    /**
     * set Validation Rules
     */
    public static function rules($id)
    {
        $rules = [
            'user_id' => 'required',
            'event_id' => 'required_without:professional_id',
            'professional_id' => 'required_without:event_id'
        ];
        return $rules;
    }

    /**
     *
     * Set Error Message
     */
    public static function messages()
    {
        /** set error message in trans files */
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * Check Validation
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, self::rules($id), self::messages());
    }

    /**
     * default sorting on created at as ascending 
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function event_detail()
    {
        return $this->hasOne(LoadCenterEvent::class, 'id', 'event_id');
    }

    public function professional_detail()
    {
        return $this->hasOne(ProfessionalProfile::class, 'id', 'professional_id');
    }
}
