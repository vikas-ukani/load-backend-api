<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class BodyParts extends Model
{

    protected $table = "body_parts";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', // Equipments name
        'code', // Equipments code generate from name
        'type', // Equipments code generate from name
        'display_id', // Display main name
        'image', // Equipments code generate from name
        'secondary_image', // Equipments code generate from name
        'is_active', // Equipment is active or not
        'parent_id', // set parent id
        'sequence', // set parent id
    ];

    /** Check Validation
     * @param $input
     * @param null $id
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, self::rules($id), self::messages());
    }

    /**
     * rules => set validation rules
     *
     * @param mixed $id
     *
     * @return array
     */
    public static function rules($id)
    {
        return [
            'name' => 'required|max:200',
            'code' => 'required|unique:body_parts,code,' . $id,
            'is_active' => 'required',
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
     * setNameAttribute => set name to Title case
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords(strtolower($value));
    }

    /** default sorting on created at as ascending
     * @param $query
     * @return mixed
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function child_details()
    {
        return $this->hasMany(BodyParts::class, 'parent_id', 'id');
    }

    /** append base url to event_image with unique
     * @param $value
     * @return string
     */
    protected function getImageAttribute($value)
    {
        $this->attributes['image'] = env('APP_URL', url('/')) . $value;
        $arr = array_unique(explode(env('APP_URL', url('/')), $this->attributes['image']));
        return $this->attributes['image'] = implode(env('APP_URL', url('/')), $arr);
    }

    /** append base url to secondary_image with unique
     * @param $value
     * @return string
     */
    protected function getSecondaryImageAttribute($value)
    {
        $this->attributes['secondary_image'] = env('APP_URL', url('/')) . $value;
        $arr = array_unique(explode(env('APP_URL', url('/')), $this->attributes['secondary_image']));
        return $this->attributes['secondary_image'] = implode(env('APP_URL', url('/')), $arr);
    }
}
