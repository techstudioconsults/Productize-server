<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatusEnum;
use App\Enums\ProductTagsEnum;
use App\Exceptions\BadRequestException;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SalesResource;
use App\Repositories\ProductRepository;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {
    }

    public function index()
    {
        $status = ProductStatusEnum::Published->value;

        $products = Product::where('status', $status)->paginate();

        return new ProductCollection($products);
    }

    /**
     * @param status - Request query of enum ProductStatusEnum. Used to filter products by status
     * @param start_date - Request query of type Date. Used aslong with end_date to filter range by date.
     * @param end_date - Request query of type Date. Used aslong with start_date to filter range by date.
     */
    public function findByUser(Request $request)
    {
        $user = Auth::user();

        $products = $this->productRepository->getUserProducts(
            $user,
            $request->status,
            $request->start_date,
            $request->end_date
        );

        return ProductResource::collection($products->paginate(10));
    }

    public function findBySlug(Product $product)
    {
        $status = ProductStatusEnum::Published->value;

        if ($product->status !== $status) {
            throw new BadRequestException();
        }

        $data = $product->data;

        $meta_data_array = [];
        foreach ($data as $value) {
            $filePath =  Str::remove(config('filesystems.disks.spaces.cdn_endpoint'), $value);

            $meta_data = $this->productRepository->getFileMetaData($filePath);

            if ($meta_data) {
                array_push($meta_data_array, $meta_data);
            }
        }

        $product_info = $this->productRepository->getProductExternal($product);

        return new JsonResponse([
            ...$product_info,
            'no_of_resources' => count($meta_data_array),
            'resources_info' => $meta_data_array,
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $validated['user_id'] = $user->id;

        // Aissign the product data to a variable
        $data = $validated['data'];
        $cover_photos = $validated['cover_photos'];
        $thumbnail = $validated['thumbnail'];

        // Take out the uploadables from the validated array to allow for mass assignment
        unset($validated['data']);
        unset($validated['cover_photos']);
        unset($validated['thumbnail']);

        $product = $this->productRepository->create(
            $validated,
            $thumbnail,
            $data, // The digital products
            $cover_photos
        );

        return new ProductResource($product);
    }


    public function show(Product $product)
    {
        $data = $product->data;
        $meta_data_array = [];

        foreach ($data as $value) {
            $filePath = Str::remove(config('filesystems.disks.spaces.cdn_endpoint'), $value);
            $meta_data = $this->productRepository->getFileMetaData($filePath);

            if ($meta_data) {
                $meta_data_array[] = $meta_data; // Simplified array pushing
            }
        }

        $productData = (new ProductResource($product))->toArray(request());

        $response = array_merge($productData, [
            'no_of_resources' => count($meta_data_array),
            'resources_info' => $meta_data_array,
        ]);

        return $response;
    }


    /**
     * Endpoint returns User Dashboard product Analytic numbers
     * @return total_products {int} - Total products uploaded by user. drafts included.
     * @return total_revenues {int} - Total revenues generated by the user on productize. i.e total sales * price
     * @return total_customers {int} - Total number of customers that has patronized autheneticated user.
     */
    public function analytics()
    {
        $user = Auth::user();

        $total_products = $this->productRepository->getTotalProductCountPerUser($user);

        $total_revenues = $this->productRepository->getUserTotalRevenues($user);

        $total_sales = $this->productRepository->getTotalSales($user);

        $total_customers = $this->productRepository->getTotalCustomers($user);

        $new_orders = $this->productRepository->getNewOrders($user);

        $result = [
            'total_products' => $total_products,
            'total_sales' => $total_sales,
            'total_customers' => $total_customers,
            'total_revenues' => $total_revenues,
            'new_orders' => $new_orders['count'],
            'new_orders_revenue' => $new_orders['revenue'],
            'views' => 1
        ];

        return new JsonResponse(['data' => $result]);
    }


    /**
     * @param status - Request query of enum ProductStatusEnum. Used to filter products by status
     * @param start_date - Request query of type Date. Used aslong with end_date to filter range by date.
     * @param end_date - Request query of type Date. Used aslong with start_date to filter range by date.
     * @param format - Request of type csv | pdf. Used to filter download to either csv or pdf.
     */
    public function downloadList(Request $request)
    {
        $user = Auth::user();

        $products = $this->productRepository->getUserProducts(
            $user,
            $request->status,
            $request->start_date,
            $request->end_date
        )->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $columns = array('Title', 'Price', 'Sales', 'Type', 'Status');

        $data = [];

        $data[] = $columns;

        $fileName = "products_$now.csv";

        foreach ($products as $product) {
            $row['Title']  = $product->title;
            $row['Price']  = $product->price;
            $row['Sales']  = 30;
            $row['Type']   = $product->product_type;
            $row['Status'] = $product->status;

            $data[] = array($row['Title'], $row['Price'], $row['Sales'], $row['Type'], $row['Status']);
        }

        $csvContent = '';
        foreach ($data as $csvRow) {
            $csvContent .= implode(',', $csvRow) . "\n";
        }

        $filePath = 'csv/' . $fileName;

        // Store the CSV content in the storage/app/csv directory
        Storage::disk('local')->put($filePath, $csvContent);

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        // Return the response with the file from storage
        return response()->stream(function () use ($filePath) {
            readfile(storage_path('app/' . $filePath));
        }, 200, $headers);
    }

    public function togglePublish(Product $product)
    {
        if ($product->trashed()) {
            throw new BadRequestException('Deleted products cannot be published or unPublished.');
        }

        $status = ProductStatusEnum::Draft->value;

        if ($product->status === $status) {
            $status = ProductStatusEnum::Published->value;
        }

        $product = $this->productRepository->update(
            $product,
            ['status' => $status]
        );

        $data = new ProductResource($product);

        return new JsonResponse([
            'link' => config('app.client_url') . "/products/$product->slug",
            'data' => $data
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        // Aissign the product data to a variable
        $data = $validated['data'] ?? null;
        $cover_photos = $validated['cover_photos'] ?? null;
        $thumbnail = $validated['thumbnail'] ?? null;

        // Take out the uploadables from the validated array to allow for mass assignment
        if ($data) {
            unset($validated['data']);
            $data = $this->productRepository->uploadData($data);
            $validated['data'] = $data;
        }

        if ($cover_photos) {
            unset($validated['cover_photos']);
            $cover_photos = $this->productRepository->uploadCoverPhoto($cover_photos);
            $validated['cover_photos'] = $cover_photos;
        }

        if ($thumbnail) {
            unset($validated['thumbnail']);
            $thumbnail = $this->productRepository->uploadThumbnail($thumbnail);
            $validated['thumbnail'] = $thumbnail;
        }

        $updated = $this->productRepository->update($product, $validated);

        return new ProductResource($updated);
    }

    public function delete(Product $product)
    {
        $product->status = 'draft';
        $product->save();
        $product->delete();

        return new ProductResource($product);
    }

    /**
     * @see \App\Providers\RouteServiceProvider
     * I had to bind the product to the route so laravel could inject the soft deleted model into the controller
     * function.
     * @see https://laracasts.com/discuss/channels/laravel/route-model-binding-with-soft-deleted-model?page=1&replyId=379334
     */
    public function restore(Product $product)
    {
        $product->restore();

        return new ProductResource($product);
    }

    public function forceDelete(Product $product)
    {
        $product->forceDelete();

        return response('product is permanently deleted');
    }

    public function sales(Product $product)
    {
        $sales = $product->sales;

        return SalesResource::collection($sales);
    }

    public function downloads()
    {
        $user = Auth::user();

        $downloads = $user->downloads;

        $products = $downloads->map(function ($download) {
            $product =  $download->product;

            return [
                'id' => $product->id,
                'title' => $product->title,
                'data' => $product->data,
                'thumbnail' => $product->thumbnail,
                'slug' => $product->slug,
                'publisher' => $product->user->full_name,
                'price' => $product->price,
            ];
        });

        return new JsonResponse($products);
    }

    public function getTopProducts()
    {
        $user = Auth::user();

        $topProducts = $user->products()
            ->join('sales', 'products.id', '=', 'sales.product_id')
            ->select('products.*', DB::raw('SUM(sales.quantity) as total_sales'))
            ->groupBy('products.id')
            ->orderByDesc('total_sales')
            ->limit(5);

        return ProductResource::collection($topProducts->paginate(5));
    }

    public function tags()
    {
        $tags = ProductTagsEnum::cases();

        return new JsonResponse(['data' => $tags]);
    }
}
