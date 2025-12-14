<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmallMcProduct extends Model
{
    use HasFactory;

    protected $table = 'small_mc_products';
    
    // Allow mass assignment for all fields
    protected $guarded = [];

    protected $fillable = [
        'item',
        'item_name',
        'amount',
        'date'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
        // Remove date cast - let it be string
    ];
    
    // Disable timestamps if causing issues
    // public $timestamps = true;
}