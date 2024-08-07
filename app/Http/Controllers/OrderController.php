<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
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
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Route handler methods for Order resource
 */
class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
    ) {}

    /**
     * @author @Intuneteq
     *
     * Retrieve a paginated listing of orders filtered by date range and product title.
     *
     * This method retrieves orders based on optional date range and product title filters.
     * The results are paginated, and only accessible to super admins.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request instance containing filter parameters.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection A collection of paginated order resources.
     */
    public function index(Request $request)
    {
        $start_date = $request->start_date;

        $end_date = $request->end_date;

        // Get the search query from the request
        $product_title = $request->product_title;

        $filter = [
            'product_title' => $product_title,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $orders = $this->orderRepository->query($filter);

        $orders = $orders->paginate(10);

        return OrderResource::collection($orders);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieves a paginated list of a orders to a user's products.
     *
     * @return \App\Http\Resources\OrderResource Returns a paginated collection of all orders on a user's products.
     */
    public function user(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        // Get the search query from the request
        $product_title = $request->product_title;

        $filter = [
            'product_title' => $product_title,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $orders = $this->orderRepository->queryRelation($user->orders(), $filter);

        $orders = $orders->paginate(10);

        return OrderResource::collection($orders);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve the specified order.
     *
     * @param  \App\Models\Order  $order  The order to display.
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
     * @param  Product  $product  The product for which to retrieve orders.
     * @return \App\Http\Resources\OrderResource A collection of order resources.
     */
    public function showByProduct(Product $product)
    {
        $filter = [
            'product_id' => $product->id,
        ];

        $orders = $this->orderRepository->query($filter)->take(3)->get();

        return OrderResource::collection($orders);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve a collection of orders associated with a specific user customer.
     *
     * @param  Customer  $customer  The customer for which to retrieve orders.
     * @return \App\Http\Resources\OrderResource A collection of order resources.
     */
    public function showByCustomer(Customer $customer)
    {
        $user = Auth::user();

        // find where the user that made the order (the customer) is the same as the user in the customer table
        $filter = [
            'orders.user_id' => $customer->user->id,
        ];

        $orders = $this->orderRepository->queryRelation($user->orders(), $filter)->get();

        return OrderResource::collection($orders);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Download a CSV file containing orders based on specified filters.
     *
     * @param  Request  $request  The HTTP request containing filters.
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
            'end_date' => $end_date,
        ];

        $orders = $this->orderRepository->queryRelation($user->orders(), $filter)->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');
        $fileName = "orders_$now.csv";

        $columns = ['Product', 'Price', 'CustomerEmail', 'Date'];
        $data = [$columns];

        foreach ($orders as $order) {
            $data[] = [
                $order->product->title,
                $order->product->price,
                $order->user->email,
                $order->created_at,
            ];
        }

        $filePath = $this->generateCsv($fileName, $data);

        return $this->streamFile($filePath, $fileName, 'text/csv');
    }

    // /**
    //  * @author @Intuneteq Tobi Olanitori
    //  *
    //  * Get the count of unseen orders for the authenticated user.
    //  *
    //  * @return JsonResource
    //  */
    // public function unseen()
    // {
    //     $user = Auth::user();

    //     $count = $this->orderRepository->queryRelation($user->orders(), ['seen' => false])->count();

    //     return new JsonResource(['count' => $count]);
    // }

    // /**
    //  * @author @Intuneteq Tobi Olanitori
    //  *
    //  * Mark all unseen orders for the authenticated user as seen.
    //  *
    //  * @return \Illuminate\Http\Resources\Json\JsonResource
    //  */
    // public function markseen()
    // {
    //     $user = Auth::user();

    //     $query = $this->orderRepository->queryRelation($user->orders(), ['seen' => false]);

    //     $query->update(['seen' => true]);

    //     return new JsonResource(['message' => 'orders marked as seen']);
    // }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve statistics about the orders.
     *
     * This method returns the total number of orders, the total revenue from orders,
     * and the average order value. These statistics are calculated from the data
     * retrieved by the OrderRepository.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource Returns a JSON resource containing order statistics.
     */
    public function stats()
    {
        $orders_query = $this->orderRepository->query([]);

        $total_orders = $orders_query->count();

        $total_orders_revenue = $orders_query->sum('total_amount');

        $avg_order_value = $total_orders ? $total_orders_revenue / $total_orders : 0;

        return new JsonResource([
            'total_orders' => $total_orders,
            'total_orders_revenue' => (int) $total_orders_revenue,
            'avg_order_value' => (int) $avg_order_value,
        ]);
    }

    public function downloadOrder(Request $request)
    {
        Auth::user();

        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $product_title = $request->product_title;

        $filter = [
            'product_title' => $product_title,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $orders = $this->orderRepository->query($filter)->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');
        $fileName = "orders_$now.csv";

        $columns = ['Product', 'Price', 'CustomerEmail', 'Date'];
        $data = [$columns];

        foreach ($orders as $order) {
            $data[] = [
                $order->product->title,
                $order->product->price,
                $order->user->email,
                $order->created_at,
            ];
        }

        $filePath = $this->generateCsv($fileName, $data);

        return $this->streamFile($filePath, $fileName, 'text/csv');

    }
}
