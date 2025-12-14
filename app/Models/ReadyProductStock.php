<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadyProductStock extends Model
{
    use HasFactory;

    protected $table = 'ready_product_stock';

    protected $fillable = [
        'date',
        'reference',
        'note',
        'total_amount',
        'total_ready_product_kg',
        'total_bags',
        'status'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_ready_product_kg' => 'decimal:3',
        'total_bags' => 'integer'
    ];

    public function items()
    {
        return $this->hasMany(ReadyProductStockItem::class);
    }
}