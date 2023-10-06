<?php

namespace App\Http\Controllers;

use App\Exceptions\UnprocessableException;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateProductStatusRequest;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PDF;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {
    }

    /**
     * @param status - Request query of enum ProductStatusEnum. Used to filter products by status
     * @param start_date - Request query of type Date. Used aslong with end_date to filter range by date.
     * @param end_date - Request query of type Date. Used aslong with start_date to filter range by date.
     */
    public function index(Request $request)
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
        return new ProductResource($product);
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

        $result = [
            'total_products' => $total_products,
            'total_sales' => $total_sales,
            'total_customers' => $total_customers,
            'total_revenues' => $total_revenues
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

        if ($request->format === 'csv') {

            $fileName = 'products.csv';

            $columns = array('Title', 'Price', 'Sales', 'Type', 'Status');

            $csvData = [];
            $csvData[] = $columns;

            foreach ($products as $product) {
                $row['Title']  = $product->title;
                $row['Price']  = $product->price;
                $row['Sales']  = 30;
                $row['Type']   = $product->product_type;
                $row['Status'] = $product->status;

                $csvData[] = array($row['Title'], $row['Price'], $row['Sales'], $row['Type'], $row['Status']);
            }

            $csvContent = '';
            foreach ($csvData as $csvRow) {
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
        } else if ($request->format === 'pdf') {

            $data = [];
            foreach ($products as $product) {
                $row['Title']  = $product->title;
                $row['Price']  = $product->price;
                $row['Sales']  = 30;
                $row['Type']   = $product->product_type;
                $row['Status'] = $product->status;

                $data[] = array($row['Title'], $row['Price'], $row['Sales'], $row['Type'], $row['Status']);
            }

            $pdfData = [
                'title' => 'What do we have here'
            ];

            $pdf = PDF::loadView('products-pdf', $pdfData);

            return $pdf->download('products.pdf');
        } else {
            throw new UnprocessableException('Invalid File Format');
        }
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        //
    }

    public function updateStatus(UpdateProductStatusRequest $request, Product $product)
    {
        $validated = $request->validated();

        $product = $this->productRepository->update($product, $validated);

        return new ProductResource($product);
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
}
