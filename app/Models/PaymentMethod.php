<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;


    protected $table = 'paymentmethod';

    protected $fillable = [
        'methodName',
        'subAccountId',
        'logo',
        'ownerAccount',
        'instruction',
    ];

    public function subAccount()
    {
        return $this->belongsTo(SubAccount::class, 'subAccountId');
    }

}

