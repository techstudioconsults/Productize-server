<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
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

    /**
     * Generate a slug for each product entity
     */
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

    /**
     * Boot method to register model event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($product) {
            $user = $product->user;

            if ($user->products()->count() <= 1) {
                // Update the first product created at property for the user
                $user->first_product_created_at = Carbon::now();
                $user->save();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function totalOrder()
    {
        return $this->hasMany(Order::class)->count();
    }

    public function totalSales()
    {
        return $this->hasMany(Order::class)->sum('quantity');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
