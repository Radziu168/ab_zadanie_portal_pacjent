<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = ['order_id', 'name', 'value', 'reference'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}