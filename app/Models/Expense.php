<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'description',
        'amount',
        'quantity_kg',
        'rate_per_kg',
        'date'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'quantity_kg' => 'decimal:3',
        'rate_per_kg' => 'decimal:2'
    ];
}