<?php

namespace App\Models;

use App\Enums\ProductStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUuids;
    use SoftDeletes;

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
        'status',
    ];

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Boot method to register model event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * @author @Intuneteq Tobi Olanitori
         */
        static::created(function ($product) {
            $user = $product->user;

            if ($user->products()->count() <= 1) {
                // Update the first product created at property for the user
                $user->first_product_created_at = Carbon::now();
                $user->save();
            }
        });
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Scope a query to order products by title match first, then description, then by tag match and then the user's full name.
     *
     * @param  Builder  $query  The query builder instance.
     * @param  string  $value  The search term
     * @return Builder The query builder instance.
     */
    public function scopeSearch(Builder $query, string $value)
    {
        return $query
            // Join the user table
            ->join('users', 'products.user_id', '=', 'users.id')

            // Ensure only published products are searched
            ->where('products.status', ProductStatusEnum::Published->value)

            // Match search query
            ->where(function ($query) use ($value) {
                $query
                    // search product title
                    ->where('products.title', 'LIKE', "%$value%")

                    // search product description
                    ->orWhere('products.description', 'LIKE', "%$value%")

                    // Raw query search through the tags column
                    ->orWhereRaw("JSON_SEARCH(products.tags, 'one', ?, NULL, '$[*]') IS NOT NULL", ["%$value%"])

                    // search user's full name
                    ->orWhere('users.full_name', 'LIKE', "%$value%");
            })
            // Select to be arranged by relevance
            // The queries that match the product title comes first.
            // The queries that match description comes second.
            // The queries that match the tag search tags follows third
            // Then the full
            // Other matches comes after.
            ->selectRaw(
                'products.*,
            CASE
                WHEN products.title LIKE ? THEN 1
                WHEN products.description LIKE ? THEN 2
                WHEN JSON_SEARCH(products.tags, \'one\', ?, NULL, \'$[*]\') IS NOT NULL THEN 3
                WHEN users.full_name LIKE ? THEN 4
                ELSE 5
            END AS relevance',
                ["%$value%", "%$value%", "%$value%", "%$value%"]
            )
            ->orderBy('relevance')
            ->orderBy('products.created_at', 'desc');
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Scope a query to include the top-selling products.
     *
     * This scope joins the products with the orders table to calculate the total sales for each product.
     * It selects all columns from the products table and adds a new column `total_sales` which represents
     * the sum of the quantity of orders for each product. The result is grouped by the product ID and
     * ordered by the total sales in descending order.
     *
     * @return Builder
     */
    public function scopeTopProducts(Builder $query)
    {
        $query->join('orders', 'products.id', '=', 'orders.product_id')
            ->select('products.*', DB::raw('SUM(orders.quantity) as total_sales'))
            ->groupBy('products.id')
            ->orderByDesc('total_sales');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
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
