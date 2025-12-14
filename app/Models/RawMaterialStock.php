<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialStock extends Model
{
    use HasFactory;

    protected $table = 'rawmaterialstock';

    protected $fillable = [
        'productId',
        'quantity',
        'purchasePrice',
        'salePrice',
        'reorderLevel'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }
}