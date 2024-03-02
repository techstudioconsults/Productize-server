<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasSlug;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'highlights' => AsArrayObject::class,
        'tags' => AsArrayObject::class,
        'cover_photos' => AsArrayObject::class,
        'data' => AsArrayObject::class,
    ];

    public function getRouteKeyName()
    {
        return 'id';
    }

    protected $guarded = [
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Sale::class, 'product_id', 'id', 'id', 'customer_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function totalOrder()
    {
        return 1;
    }

    public function totalSales()
    {
        return 1;
    }
}
