<?php

namespace App\Repositories;

use App\Enums\ProductStatusEnum;
use App\Events\Products;
use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class ProductRepository
{
    public function __construct(
        public UserRepository $userRepository
    ) {
    }

    /**
     * @return PRODUCT This will return a pre called instance of PRODUCT
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

        event(new Products($product));

        return $product;
    }

    public function getTotalProductCountPerUser(User $user): int
    {
        return Product::where('user_id', $user->id)->count();
    }

    public function getTotalOrder(Product $product)
    {
        return Sale::where('product_id', $product->id)->sum('quantity');
    }

    public function getTotalSales(User $user): int
    {
        return $this->userRepository->getTotalSales($user);
    }

    public function getUserTotalRevenues(User $user): int
    {
        return $this->userRepository->getTotalRevenues($user);
    }

    public function getTotalCustomers(User $user): int
    {
        return $this->userRepository->getTotalCustomers($user);
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
