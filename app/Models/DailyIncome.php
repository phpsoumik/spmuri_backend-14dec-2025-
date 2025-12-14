<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyIncome extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'date',
        'amount',
        'purpose'
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'amount' => 'decimal:2'
    ];
    
    protected $dates = ['date'];
}