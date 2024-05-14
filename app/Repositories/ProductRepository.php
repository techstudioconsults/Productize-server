<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 08-05-2024
 */

namespace App\Repositories;

use App\Enums\ProductStatusEnum;
use App\Events\Products;
use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;


/**
 * @author Tobi Olanitori
 *
 * Repository for Products
 */
class ProductRepository
{
    /**
     * Constructor for ProductRepository.
     *
     * @param \App\Repositories\UserRepository - User repository class
     * @see \App\Repositories\UserRepository   Constructor with UserRepository dependency injection.
     * @return void {@see UserRepository}
     */
    public function __construct(
        public UserRepository $userRepository
    ) {
    }

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

    public function find(?array $filter = null): Builder
    {
        $query = Product::query();

        if ($filter === null) return $query;

        // $this->applyDateFilters($query, $filter);

        // For each filter array, entry, validate presence in model and query
        foreach ($filter as $key => $value) {
            if (Schema::hasColumn('products', $key)) {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * getUserProducts
     *
    //  * @return void
     */
    public function getUserProducts(
        User $user,
        ?string $status = null,
        ?string $start_date = null,
        ?string $end_date = null
    ) {
        $products = Product::where('user_id', $user->id);

        /**
         * Filter products by Product status
         */
        if ($status && $status === 'deleted') {
            $products->onlyTrashed();
        } else if ($status && $status !== 'deleted') {
            // Validate status
            $validator = Validator::make(['status' => $status], [
                'status' => ['required', new Enum(ProductStatusEnum::class)]
            ]);

            if ($validator->fails()) {
                throw new BadRequestException($validator->errors()->first());
            }

            $products->where('status', $status);
        }

        /**
         * Filter by date of creation
         * start date reps the date to start filtering from
         * end_date reps the date to end the filtering
         */
        if ($start_date && $end_date) {

            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date
            ], [
                'start_date' => 'date',
                'end_date' => 'date'
            ]);

            if ($validator->fails()) {
                throw new UnprocessableException($validator->errors()->first());
            }

            $products->whereBetween('created_at', [$start_date, $end_date]);
        }

        return $products;
    }

    public function getProductExternal(Product $product)
    {
        $total_order = $this->getTotalOrder($product);
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
            'total_orders' => $total_order
        ];
    }

    public function getProductBySlug(string $slug)
    {
        return Product::firstWhere('slug', $slug);
    }

    /**
     * @Intuneteq
     *
     * @param credentials {array} - All products properties except uploadables e.g thumbanails, cover photos etc.
     * @param thumbnail {mixed} - Uploaded thumbnail file object. Must be an Image
     * @param data {mixed} - uploaded Product file object
     * @param cover_photos {array} - Array of image file objects. Must be an array of Image.
     *
     * @uses App\Events\Products
     *
     * @return Product
     *
     * For more details, see {@link \App\Http\Requests\StoreProductRequest}.
     */
    public function create(array $credentials, mixed $thumbnail, mixed $data, array $cover_photos)
    {
        $thumbnail = $this->uploadThumbnail($thumbnail);

        $cover_photos = $this->uploadCoverPhoto($cover_photos);

        $data = $this->uploadData($data);

        $credentials['data'] = $data;
        $credentials['cover_photos'] = $cover_photos;
        $credentials['thumbnail'] = $thumbnail;

        $product = Product::create($credentials);

        return $product;
    }

    public function getTotalProductCountPerUser(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ): int {
        $products = Product::where('user_id', $user->id);

        if ($start_date && $end_date) {
            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date
            ], [
                'start_date' => 'date',
                'end_date' => 'date'
            ]);

            if ($validator->fails()) {
                throw new UnprocessableException($validator->errors()->first());
            }

            $products->whereBetween('created_at', [$start_date, $end_date]);
        }

        return $products->count();
    }

    public function getTotalOrder(Product $product)
    {
        return $product->totalOrder();
    }

    public function getTotalSales(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ): int {
        return $this->userRepository->getTotalSales($user, $start_date, $end_date);
    }

    public function getUserTotalRevenues(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ): int {
        return $this->userRepository->getTotalRevenues($user, $start_date, $end_date);
    }

    public function getTotalCustomers(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ): int {
        return $this->userRepository->getTotalCustomers($user, $start_date, $end_date);
    }

    public function getNewOrders(User $user)
    {
        $two_days_ago = Carbon::now()->subDays(2);

        $new_orders = $user->orders->whereBetween('created_at', [$two_days_ago, now()]);

        $result = [
            'count' => $new_orders->count(),
            'revenue' => $new_orders->sum('total_amount')
        ];

        return $result;
    }

    public function update(Product $product, array $updatables)
    {

        foreach ($updatables as $column => $value) {
            $product->$column = $value;
        }

        $product->save();

        return $product;
    }

    public function uploadData(mixed $data)
    {
        $uploadedData = [];

        foreach ($data as $item => $file) {
            $originalName = str_replace(' ', '', $file->getClientOriginalName());

            $path = Storage::putFileAs('digital-products', $file, $originalName);

            $url = config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path;

            array_push($uploadedData, $url);
        }

        return $uploadedData;
    }

    public function uploadCoverPhoto(mixed $cover_photos)
    {
        $uploadedCoverPhotos = [];
        foreach ($cover_photos as $item => $file) {
            $originalName = str_replace(' ', '', $file->getClientOriginalName());

            $path = Storage::putFileAs('products-cover-photos', $file, $originalName);

            $url = config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path;

            array_push($uploadedCoverPhotos, $url);
        }

        return $uploadedCoverPhotos;
    }

    public function uploadThumbnail($thumbnail)
    {
        $thumbnailPath = Storage::putFileAs(
            'products-thumbnail',
            $thumbnail,
            str_replace(' ', '', $thumbnail->getClientOriginalName())
        );

        return config('filesystems.disks.spaces.cdn_endpoint') . '/' . $thumbnailPath;
    }

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
}
