<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Customer extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_owner_id',
        'buyer_id',
        'latest_puchase_id'
    ];


    // public function user(): HasOneThrough
    // {
    //     return $this->hasOneThrough(User::class, Product::class);
    // }

    public function user()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // public function orders()
    // {
    //     // return $this->user;
    //     // return $this->hasManyThrough(User::class,  'buyer_id');
    //     // return $this->hasManyThrough(ProductOrder::class, Order::class, 'buyer_id', 'order_id');
    // }

    public function product()
    {
        return $this->belongsTo(Product::class, 'latest_puchase_id');
    }
}
