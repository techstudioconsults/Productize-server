<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function scopeLatestOfMany(Builder $query, $column)
    {
        return $query->whereIn('id', function ($subquery) use ($column) {
            $subquery->selectRaw('MAX(id)')
                ->from('customers')
                ->groupBy($column);
        });
    }

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
}
