<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Order extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'reference_no',
        'user_id',
        'product_id',
        'quantity',

    ];

    // user who made the order
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // public function customer(): BelongsTo
    // {
    //     return $this->belongsTo(Customer::class);
    // }

    // public function buyer()
    // {
    //     return $this->belongsTo(User::class);
    // }

    // public function product()
    // {
    //     return $this->belongsTo(Product::class);
    // }
}
