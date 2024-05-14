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
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Validator as Validation;
use Illuminate\Validation\Rules\Enum;


/**
 * @author Tobi Olanitori
 *
 * Repository for Products
 */
class ProductRepository
{
    private ?Validator $validator = null;

    /**
     * Constructor for ProductRepository.
     *
     * @param \App\Repositories\UserRepository - User repository class
     *
     * @see \App\Repositories\UserRepository   Constructor with UserRepository dependency injection.
     * @return void
     */
    public function __construct(
        public UserRepository $userRepository
    ) {
    }

    public function getValidator(): ?Validator
    {
        return $this->validator;
    }

    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
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
            $rules = [
                'status' => ['required', new Enum(ProductStatusEnum::class)]
            ];

            if ($this->isValidated(['status' => $status], $rules))
                throw new BadRequestException($this->getValidator()->errors()->first());

            $products->where('status', $status);
        }

        /**
         * Filter by date of creation
         * start date reps the date to start filtering from
         * end_date reps the date to end the filtering
         */
        if ($start_date && $end_date) {

            $validator = Validation::make([
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
     * @param array credentials All products properties required in the StoreProductRequest along with the user_id.
     * @uses App\Events\Products
     *
     * @return Product
     *
     * @see \App\Http\Requests\StoreProductRequest
     */
    public function create(array $credentials)
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

    public function getTotalProductCountPerUser(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ): int {
        $products = Product::where('user_id', $user->id);

        if ($start_date && $end_date) {
            $validator = Validation::make([
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

    private function isValidated(array $data, array $rules): bool
    {
        // Validate the credentials
        $validator = validator($data, $rules);

        // Check if validation fails
        if ($validator->fails()) {
            $this->setValidator($validator);

            return false;
        };

        return true;
    }
}
