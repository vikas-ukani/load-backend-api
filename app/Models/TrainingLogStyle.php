<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingLogStyle extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** default sorting on created at as ascending
     * @param $query
     * @return mixed
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {
    //         # creating code...
    //     });
    // }
}
