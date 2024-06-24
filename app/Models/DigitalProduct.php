<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalProduct extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'category'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function resources()
    {
        return $this->hasManyThrough(ProductResource::class, Product::class, 'id', 'product_id', 'product_id', 'id');
    }

}
