<?php

namespace App\Models;

use Illuminate\Validation\Validator;
use Illuminate\Database\Eloquent\Model;

class CustomTrainingPrograms extends Model
{
    //
    /** table name */
    protected $table = "custom_training_programs";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', // title name
        'code', // from title 
        'is_active', // active or not
        'parent_id', // parent from this table
        'created_at',
        'updated_at',
    ];


    /** 
     * To Set First Letter Caps
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = ucfirst(strtolower($value));
    }

    /**
     * set Validation Rules
     */
    public static function rules($id)
    {
        $rules = [
            'title' => 'required|max:200',
            'code' => 'required|max:200',
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


    /**
     * parent_detail => get {Parent Details
     *
     * @return void
     */
    public function parent_detail()
    {
        return $this->hasOne("App\Models\CustomTrainingPrograms", 'id', 'parent_id');
    }
}
