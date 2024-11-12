<?php

/**
 * @author Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 08-05-2024
 */

namespace App\Repositories;

use App\Enums\ProductStatusEnum;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductSearch;
use App\Models\User;
use App\Traits\HasStatusFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * Repository for Product resource
 */
class ProductRepository extends Repository
{
    use HasStatusFilter;

    const COVER_PHOTOS_PATH = 'products-cover-photos';

    const THUMBNAIL_PATH = 'products-thumbnail';

    const PUBLISHED = 'published';

    const DRAFT = 'draft';

    const DELETED = 'deleted';

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Create a new product with the provided credentials.
     *
     * @param  array  $credentials  An array containing all product properties required in the StoreProductRequest,
     *                              along with the user_id.
     * @return \App\Models\Product The newly created product instance.
     *
     * @throws \App\Exceptions\BadRequestException If validation of the provided credentials fails.
     */
    public function create(array $credentials): Product
    {
        // Get the validation rules from the StoreProductRequest
        $rules = (new StoreProductRequest)->rules();

        // Add the 'user_id' rule to the validation rules
        $rules['user_id'] = 'required';

        if (! $this->isValidated($credentials, $rules)) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        // $data = $credentials['data'];
        $cover_photos = $credentials['cover_photos'];
        $thumbnail = $credentials['thumbnail'];

        // Upload the product's thumbnail to digital ocean's space
        $thumbnail = $this->uploadThumbnail($thumbnail);

        // Upload the product's cover photos to digital ocean's space
        $cover_photos = $this->uploadCoverPhoto($cover_photos);

        // Update the credentials array
        // $credentials['data'] = $data;
        $credentials['cover_photos'] = $cover_photos;
        $credentials['thumbnail'] = $thumbnail;

        $product = Product::create($credentials);

        return $product;
    }

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Create a query builder for products based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return \Illuminate\Database\Eloquent\Builder The query builder for products.
     */
    public function query(array $filter): Builder
    {
        $query = Product::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        $this->applyStatusFilter($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find products based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply.
     * @return Collection|null A collection of found products, or null if none are found.
     */
    public function find(?array $filter = []): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a product by its ID.
     *
     * @param  string  $id  The ID of the product to find.
     * @return \App\Models\Product|null The found product, or null if not found.
     */
    public function findById(string $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single product based on the provided filter criteria.
     *
     * @param  array  $filter  The filter criteria to search for the product.
     * @return \App\Models\Product|null The found product, or null if not found.
     *
     * @throws \App\Exceptions\ApiException If an unexpected error occurs during the search.
     */
    public function findOne(array $filter): ?Product
    {
        try {
            return Product::where($filter)->first();
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), 500);
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve top products based on sales within a specified date range.
     *
     * @param  array|null  $filter  An optional array of filters including 'start_date' and 'end_date'.
     * @return Builder The query builder instance.
     *
     * @see \App\Models\Product scope methods for TopProducts query defined.
     */
    public function topProducts(?array $filter = []): Builder
    {
        $query = Product::TopProducts();

        // Apply date filter specifically to the products table
        if (isset($filter['start_date']) && isset($filter['end_date'])) {
            $query->whereBetween('products.created_at', [$filter['start_date'], $filter['end_date']]);
        }

        unset($filter['start_date'], $filter['end_date']);

        $this->applyStatusFilter($query, $filter);

        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Updates a product model with the provided updatable attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $product  The product model to update.
     * @param  array  $updatables  An array of updatable attributes for the product.
     * @return \App\Models\Product The updated product model.
     *
     * @throws \App\Exceptions\ModelCastException If the provided model is not an instance of Product.
     * @throws \App\Exceptions\BadRequestException If any of the provided updatable attributes fail validation.
     */
    public function update(Model $product, array $updatables): Product
    {
        if (! $product instanceof Product) {
            throw new ModelCastException('Product', get_class($product));
        }
        // Get the validation rules from the StoreProductRequest
        $rules = (new UpdateProductRequest)->rules();

        if (! $this->isValidated($updatables, $rules)) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        if (isset($updatables['cover_photos'])) {
            $cover_photos = $this->uploadCoverPhoto($updatables['cover_photos']);
            $updatables['cover_photos'] = $cover_photos;
        }

        if (isset($updatables['thumbnail'])) {
            $thumbnail = $this->uploadThumbnail($updatables['thumbnail']);
            $updatables['thumbnail'] = $thumbnail;
        }

        foreach ($updatables as $column => $value) {
            $product->$column = $value;
        }

        $product->save();

        return $product;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Searches for products based on the given text.
     *
     * The search scope includes:
     * - Matching the product title
     * - Matching the product description
     * - Matching tags within the JSON tags column
     * - Matching the full name of the associated user
     *
     * Results are ordered by relevance:
     * 1. Title matches
     * 2. Description matches
     * 3. Tag matches
     * 4. User full name matches
     *
     * @param  string  $text  The text to search for.
     * @return Builder The query builder instance with the applied search conditions.
     *
     * @see \App\Models\Product scope methods for search query defined.
     */
    public function search(string $text): Builder
    {
        return Product::Search($text);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Store the user's searched product IDs.
     *
     * @param  Product  $product  The product searched by the user.
     * @param  User  $user  The user whose searches are being tracked.
     */
    public function trackSearch(Product $product, User $user): void
    {
        $upserts = [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ];

        ProductSearch::upsert(
            [$upserts],
            uniqueBy: ['user_id', 'product_id'],
        );
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve the last 10 products a user has searched for.
     *
     * This method fetches the last 10 products that a user has searched for
     * based on their search history. It queries the ProductSearch model to get
     * the recent searches, then retrieves the corresponding products from the
     * Product model.
     *
     * @param  User  $user  The logged-in user whose search history is being retrieved.
     * @return Collection|null A collection of found products, or null if none are found.
     */
    public function findSearches(User $user): ?Collection
    {
        // Get the last 10 products the user searched for
        $searches = ProductSearch::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Get the product ids from the searches
        $product_ids = $searches->pluck('product_id');

        // Retrieve product information for the last 10 searched products - where they are still published.
        return Product::whereIn('id', $product_ids)->where('status', self::PUBLISHED)->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Uploads an array of cover photos and returns their storage paths.
     *
     * @param  array  $cover_photos  An array of cover photo image files to upload.
     * @return array An array containing the storage paths of the uploaded cover photos.
     *
     * @throws BadRequestException If any of the provided cover photos fail validation.
     */
    public function uploadCoverPhoto(array $cover_photos): array
    {
        // Each item in the 'data' array must be an image
        if (! $this->isValidated($cover_photos, ['required|image'])) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        return collect($cover_photos)->map(function ($file) {
            $original_name = str_replace(' ', '', $file->getClientOriginalName());

            $path = Storage::putFileAs(self::COVER_PHOTOS_PATH, $file, $original_name);

            return config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path;
        })->all();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Uploads a product's thumbnail image and returns its storage path.
     *
     * @param  object  $thumbnail  The thumbnail image file to upload.
     * @return string The storage path of the uploaded thumbnail.
     *
     * @throws BadRequestException If the provided thumbnail fails validation.
     */
    public function uploadThumbnail(object $thumbnail): string
    {
        // Each item in the 'data' array must be a file
        if (! $this->isValidated([$thumbnail], ['required|image'])) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        $thumbnailPath = Storage::putFileAs(
            self::THUMBNAIL_PATH,
            $thumbnail,
            str_replace(' ', '', $thumbnail->getClientOriginalName())
        );

        return config('filesystems.disks.spaces.cdn_endpoint') . '/' . $thumbnailPath;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Check if the product is part of the user's recent searches.
     *
     * @param  Product  $product  The product to check.
     * @param  array|string|null  $cookie  The cookie from the incoming request instance.
     * @return bool Returns true if the product is in the search history, false otherwise.
     */
    public function isSearchedProduct(Product $product, array|string|null $cookie): bool
    {
        $product_ids = json_decode($cookie, true) ?? [];

        return in_array($product->id, $product_ids);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Ensure the product status is published.
     *
     * @param  Product  $product  The product to check.
     * @return bool It returns true if the product status is published, false otherwise.
     */
    public function isPublished(Product $product): bool
    {
        if ($product->status !== ProductStatusEnum::Published->value) {
            return false;
        }

        return true;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Prepare products for the cart.
     */
    public function prepareProducts(array $cart): array
    {
        $rules = [
            '*.product_slug' => 'required|string',
            '*.quantity' => 'required|integer|min:1',
        ];

        if (! $this->isValidated($cart, $rules)) {
            throw new ServerErrorException('Invalid parameter called with prepareProducts ');
        }

        return Arr::map($cart, function ($item) {
            $slug = $item['product_slug'];

            $product = $this->query(['slug' => $slug])->first();

            if (! $product) {
                throw new BadRequestException('Product with slug ' . $slug . ' not found');
            }

            if ($product->status !== 'published') {
                throw new BadRequestException('Product with slug ' . $slug . ' not published');
            }

            $price = $product->price;

            if ($product->discount_price > 0) {
                $price = $product->discount_price;
            }

            $amount = $price * $item['quantity'];

            $share = $amount - ($amount * RevenueRepository::SALE_COMMISSION);

            return [
                'product_id' => $product->id,
                'amount' => $amount,
                'quantity' => $item['quantity'],
                'share' => $share,
                'price' => $price,
            ];
        });
    }
}
