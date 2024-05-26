<?php

/**
 *  @author @Intuneteq Tobi Olanitori
 * @version 1.0
 * @since 26-05-2024
 */

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\OrderRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Storage;

/**
 * Route handler methods for Order resource
 */
class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository
    ) {
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieves a paginated list of a orders to a user's products.
     *
     * @return \App\Http\Resources\OrderResource Returns a paginated collection of all orders on a user's products.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        // Get the search query from the request
        $product_title = $request->product_title;

        $filter = [
            'product_title' => $product_title,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        $orders = $this->orderRepository->queryRelation($user->orders(), $filter);

        $orders = $orders->paginate(10);

        return OrderResource::collection($orders);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrive the specified order.
     *
     * @param  \App\Models\Order  $order The order to display.
     * @return \App\Http\Resources\OrderResource Returns a resource representing the queried order.
     */
    public function show(Order $order)
    {
        return new OrderResource($order);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve a collection of orders associated with a specific product.
     *
     * It returns the first 3 in the collection.
     *
     * @param Product $product The product for which to retrieve orders.
     * @return \App\Http\Resources\OrderResource A collection of order resources.
     */
    public function showByProduct(Product $product)
    {
        $filter = [
            'product_id' => $product->id
        ];

        $orders = $this->orderRepository->query($filter)->take(3)->get();

        return OrderResource::collection($orders);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve a collection of orders associated with a specific user customer.
     *
     * @param Customer $customer The customer for which to retrieve orders.
     *  @return \App\Http\Resources\OrderResource A collection of order resources.
     */
    public function showByCustomer(Customer $customer)
    {
        $user = Auth::user();

        $filter = [
            'user_id' => $customer->user->id
        ];

        $orders = $this->orderRepository->queryRelation($user->orders(), $filter)->get();

        return OrderResource::collection($orders);
    }

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Download a CSV file containing orders based on specified filters.
     *
     * @param Request $request The HTTP request containing filters.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse The streamed CSV file response.
     */
    public function downloadList(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        // Get the search query from the request
        $product_title = $request->product_title;

        $filter = [
            'product_title' => $product_title,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        $orders = $this->orderRepository->queryRelation($user->orders(), $filter)->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $columns = array('Product', 'Price', 'CustomerEmail', 'Date');

        $data = [];

        $data[] = $columns;

        $fileName = "orders_$now.csv";

        foreach ($orders as $order) {
            $row['Product']  = $order->product->title;
            $row['Price']  = $order->product->price;
            $row['CustomerEmail']  = $order->user->email;
            $row['Date']   = $order->created_at;

            $data[] = array($row['Product'], $row['Price'], $row['CustomerEmail'], $row['Date']);
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

            // Delete the file after reading
            Storage::disk('local')->delete($filePath);
        }, 200, $headers);
    }
}
