<?php

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

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository
    ) {
    }

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

    public function show(Order $order)
    {
        return new OrderResource($order);
    }

    public function showByProduct(Product $product)
    {
        $filter = [
            'product_id' => $product->id
        ];

        $orders = $this->orderRepository->query($filter)->take(3)->get();

        return OrderResource::collection($orders);
    }

    public function showByCustomer(Customer $customer)
    {
        $user = Auth::user();

        $filter = [
            'user_id' => $customer->user->id
        ];

        $orders = $this->orderRepository->queryRelation($user->orders(), $filter)->get();

        return OrderResource::collection($orders);
    }

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
        }, 200, $headers);
    }
}
