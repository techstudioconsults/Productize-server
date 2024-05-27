<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 08-05-2024
 */

namespace App\Repositories;

use App\Enums\ProductStatusEnum;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;


/**
 * @author @Intuneteq Tobi Olanitori
 *
 * Repository for Product resource
 */
class ProductRepository extends Repository
{
    const PRODUCT_DATA_PATH = "digital-products";
    const COVER_PHOTOS_PATH = "products-cover-photos";
    const THUMBNAIL_PATH = "products-thumbnail";

    public function seed(): void
    {
        $users = User::factory(5)->create();

        foreach ($users as $user) {
            // Create 5 products for each user
            Product::factory()
                ->count(5)
                ->state(new Sequence(
                    ['status' => 'published'],
                    ['status' => 'draft'],
                ))
                ->create(['user_id' => $user->id, 'price' => '100000']);
        }
    }

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Create a new product with the provided credentials.
     *
     * @param array $credentials An array containing all product properties required in the StoreProductRequest,
     *                           along with the user_id.
     *
     * @return \App\Models\Product The newly created product instance.
     *
     * @throws \App\Exceptions\BadRequestException If validation of the provided credentials fails.
     */
    public function create(array $credentials): Product
    {
        // Get the validation rules from the StoreProductRequest
        $rules = (new StoreProductRequest())->rules();

        // Add the 'user_id' rule to the validation rules
        $rules['user_id'] = 'required';

        if (!$this->isValidated($credentials, $rules)) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        $data = $credentials['data'];
        $cover_photos = $credentials['cover_photos'];
        $thumbnail = $credentials['thumbnail'];

        // Upload the product's data to digital ocean's space
        $data = $this->uploadData($data);

        // Upload the product's thumbnail to digital ocean's space
        $thumbnail = $this->uploadThumbnail($thumbnail);

        // Upload the product's cover photos to digital ocean's space
        $cover_photos = $this->uploadCoverPhoto($cover_photos);

        // Update the credentials array
        $credentials['data'] = $data;
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
     * @param array $filter The filter criteria to apply.
     *
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
     * @param array|null $filter The filter criteria to apply.
     *
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
     * @param string $id The ID of the product to find.
     *
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
     * @param array $filter The filter criteria to search for the product.
     *
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
     * @deprecated This method will be deleted when product data table is implemented
     */
    public function getProductExternal(Product $product)
    {
        return [
            'title' => $product->title,
            'thumbnail' => $product->thumbnail,
            'price' => $product->price,
            'publisher' => $product->user->full_name,
            'publisher_logo' => $product->user->logo,
            'slug' => $product->slug,
            'highlights' => $product->highlights,
            'product_type' => $product->product_type,
            'cover_photos' => $product->cover_photos,
            'tags' => $product->tags,
            'description' => $product->description,
            'total_orders' => $product->totalOrder()
        ];
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve top products based on sales within a specified date range.
     *
     * @param array|null $filter An optional array of filters including 'start_date' and 'end_date'.
     *
     * @return Builder The query builder instance.
     */
    public function topProducts(?array $filter = []): Builder
    {
        $query = Product::query();

        $query->join('orders', 'products.id', '=', 'orders.product_id')
            ->select('products.*', DB::raw('SUM(orders.quantity) as total_sales'))
            ->groupBy('products.id')
            ->orderByDesc('total_sales');

        // Apply date filter specifically to the products table
        if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
            $query->whereBetween('products.created_at', [$filter['start_date'], $filter['end_date']]);

            unset($filter['start_date'], $filter['end_date']);
        }

        $this->applyStatusFilter($query, $filter);


        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Updates a product model with the provided updatable attributes.
     *
     * @param \Illuminate\Database\Eloquent\Model $product The product model to update.
     * @param array $updatables An array of updatable attributes for the product.
     *
     * @return \App\Models\Product The updated product model.
     *
     * @throws \App\Exceptions\ModelCastException If the provided model is not an instance of Product.
     * @throws \App\Exceptions\BadRequestException If any of the provided updatable attributes fail validation.
     */
    public function update(Model $product, array $updatables): Product
    {
        if (!$product instanceof Product) {
            throw new ModelCastException("Product", get_class($product));
        }
        // Get the validation rules from the StoreProductRequest
        $rules = (new UpdateProductRequest())->rules();

        if (!$this->isValidated($updatables, $rules)) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        if (isset($updatables['data'])) {
            $data = $this->uploadData($updatables['data']);
            $updatables['data'] = $data;
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
     * Uploads an array of the product's data files and returns their storage paths.
     *
     * The data is the actual resource being sold.
     *
     * @param array $data An array of data files to upload.
     *
     * @return array An array containing the storage paths of the uploaded data files.
     *
     * @throws BadRequestException If any of the provided data files fail validation.
     */
    public function uploadData(array $data): array
    {
        // Each item in the 'data' array must be a file
        if (!$this->isValidated($data, ['required|file'])) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        return collect($data)->map(function ($file) {
            $originalName = str_replace(' ', '', $file->getClientOriginalName());

            $path = Storage::putFileAs(self::PRODUCT_DATA_PATH, $file, $originalName);

            return config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path;
        })->all();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Uploads an array of cover photos and returns their storage paths.
     *
     * @param array $cover_photos An array of cover photo image files to upload.
     *
     * @return array An array containing the storage paths of the uploaded cover photos.
     *
     * @throws BadRequestException If any of the provided cover photos fail validation.
     */
    public function uploadCoverPhoto(array $cover_photos): array
    {
        // Each item in the 'data' array must be an image
        if (!$this->isValidated($cover_photos, ['required|image'])) {
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
     * @param object $thumbnail The thumbnail image file to upload.
     *
     * @return string The storage path of the uploaded thumbnail.
     *
     * @throws BadRequestException If the provided thumbnail fails validation.
     */
    public function uploadThumbnail(object $thumbnail): string
    {
        // Each item in the 'data' array must be a file
        if (!$this->isValidated([$thumbnail], ['required|image'])) {
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
     * Retrieve file metadata for a given file path.
     *
     * @param string $filePath The path of the file.
     *
     * @return array|null An array containing file metadata including size and MIME type, or null if the file doesn't exist.
     */
    public function getFileMetaData(string $filePath)
    {
        if (Storage::disk('spaces')->exists($filePath)) {
            $size = Storage::size($filePath);
            $mime_Type = Storage::mimeType($filePath);

            return [
                'size' =>  round($size / 1048576, 2) . 'MB', // Convert byte to MB
                'mime_type' => $mime_Type
            ];
        } else {
            return null;
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Apply a status filter to the query based on the provided status value.
     * It removes the status key and value from the array.
     *
     * @param Builder|Relation $query The query builder or relation instance.
     * @param array &$filter The filter array containing the status key.
     *
     * @throws BadRequestException If the status value fails validation.
     */
    private function applyStatusFilter(Builder | Relation $query, array &$filter)
    {
        if (isset($filter['status'])) {
            $status = $filter['status'];

            if ($status === 'deleted') {
                $query->onlyTrashed();
            } else if ($status && $status !== null && $status !== 'deleted') {
                // Validate status
                $rules = [
                    'status' => ['required', new Enum(ProductStatusEnum::class)]
                ];

                if (!$this->isValidated(['status' => $status], $rules)) {
                    throw new BadRequestException($this->getValidator()->errors()->first());
                }

                $query->where('status', $status);
            }
        }
        unset($filter['status']);
    }
}
