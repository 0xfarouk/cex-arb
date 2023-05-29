<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orderbook extends Model
{
    use HasFactory;

    protected $table = 'order_book';

    protected $guarded = ['id'];

//    protected $casts = [
//        'order_book' => 'array'
//    ];
}
