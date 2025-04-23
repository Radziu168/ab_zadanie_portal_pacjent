<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['patient_id'];

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}