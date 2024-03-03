<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // user who made the order
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function totalOrder()
    {
        return $this->belongsTo(Order::class)->count();
    }

    // public function product()
    // {
    //     return $this->belongsTo(Product::class, 'latest_puchase_id');
    // }
}
