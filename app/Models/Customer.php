<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Customer extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'email',
        'user_id',
        'latest_puchase_id'
    ];


    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Product::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
