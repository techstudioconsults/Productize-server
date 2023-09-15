<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'paystack_customer_code',
        'paystack_subscription_id',
        'user_id',
    ];

    protected $hidden = [
        'paystack_customer_code',
        'paystack_subscription_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
