<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 08-05-2024
 */

namespace App\Http\Controllers;

use App\Enums\DigitalProductCategory;
use App\Enums\ProductEnum;
use App\Enums\ProductStatusEnum;
use App\Enums\ProductTagsEnum;
use App\Enums\SkillSellingCategory;
use App\Exceptions\BadRequestException;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ExternalProductResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Mail\BestSellerCongratulations;
use App\Models\Product;
use App\Notifications\ProductPublished;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Mail;

/**
 * Route handler methods for Product resource
 */
class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected UserRepository $userRepository,
        protected OrderRepository $orderRepository,
        protected CustomerRepository $customerRepository
    ) {}

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve a paginated listing of products based on optional date filters.
     *
     * This method fetches products from the ProductRepository based on the provided
     * start and end dates. If no dates are provided, all products are retrieved.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request containing optional start_date and end_date filters.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Returns a collection of ProductResource instances representing the retrieved products.
     */
    public function index(Request $request)
    {
        $filter = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $products = $this->productRepository->query($filter)->paginate(10);

        return ProductResource::collection($products);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieves a paginated list of published products.
     *
     * @return ProductCollection Returns a paginated collection of published products.
     */
    public function external()
    {
        $status = ProductStatusEnum::Published->value;

        $products = $this->productRepository->query(['status' => $status])->latest()->get();

        return ExternalProductResource::collection($products);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve products belonging to the authenticated user based on the provided filters.
     *
     * @param  Request  $request  The HTTP request containing query parameters:
     *                            - status: (optional) Filter products by status (enum ProductStatusEnum).
     *                            - start_date: (optional) Filter products created on or after this date.
     *                            - end_date: (optional) Filter products created on or before this date.
     *
     * @see \App\Enums\ProductStatusEnum
     *
     * @return Collection<ProductResource> Returns a collection of ProductResource instances.
     */
    public function user(Request $request)
    {
        $user = Auth::user();

        $filter = [
            'user_id' => $user->id,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $query = $this->productRepository->query($filter);

        // Paginate the results
        $paginatedProducts = $query->paginate(10);

        // Append the query parameters to the pagination links
        $paginatedProducts->appends($request->query());

        return ProductResource::collection($paginatedProducts);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrive the specified product.
     *
     * @param  \App\Models\Product  $product  The product to display.
     * @return array Returns an array containing detailed information about the product and its associated resources.
     */
    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve product information by its slug.
     *
     * This method retrieves detailed information about a product identified by its slug,
     * including checking if the product is part of the user's recent searches, tracking
     * user interest, and fetching associated metadata.
     *
     * @param  Product  $product  The product identified by its slug.
     * @param  Request  $request  The incoming request instance.
     * @return JsonResponse Returns a JSON response containing detailed information about the product and its associated resources.
     *
     * @throws BadRequestException Throws a BadRequestException if the product status is not published.
     */
    public function slug(Product $product, Request $request)
    {
        // Retrieve the user if they are logged in - If a token is sent with the request.
        $user = $request->user('sanctum');
        $cookie = $request->cookie('search_term');

        if ($this->productRepository->isSearchedProduct($product, $cookie) && $user) {
            $this->productRepository->trackSearch($product, $user);
        }

        if (! $this->productRepository->isPublished($product)) {
            throw new BadRequestException();
        }

        return new ExternalProductResource($product);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new product.
     *
     * @param  \App\Http\Requests\StoreProductRequest  $request  The incoming request containing validated product data.
     * @return \App\Http\Resources\ProductResource Returns a resource representing the newly created product.
     */
    public function store(StoreProductRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $validated['user_id'] = $user->id;

        $product = $this->productRepository->create($validated);

        return new ProductResource($product);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update a given product.
     *
     * @param  \App\Http\Requests\UpdateProductRequest  $request  The incoming request containing validated product update data.
     * @return \App\Http\Resources\ProductResource Returns a resource representing the newly created product.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $updated = $this->productRepository->update($product, $validated);

        return new ProductResource($updated);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Endpoint returns User Dashboard product Analytic numbers
     *
     * @param  Request  $request  The HTTP request object containing filter parameters.
     * @return JsonResponse A JSON response containing user dashboard analytics.
     *
     * The response contains the following keys:
     * - total_products (int): Total products uploaded by user, drafts included.
     * - total_revenues (int): Total revenues generated by the user on Productize (total sales * price).
     * - total_sales (int): Total number of sales made by the user (Total Orders).
     * - total_customers (int): Total number of customers that have patronized the authenticated user.
     * - new_orders (int): Number of new orders in the last 2 days.
     * - new_orders_revenue (int): Revenue from new orders in the last 2 days.
     * - views (int): Currently hardcoded to 1 (should be updated to reflect actual view of user products).
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $order_query = $this->orderRepository->queryRelation($user->orders(), $filter);

        $total_products = $this->productRepository->query([...$filter, 'user_id' => $user->id])->count();

        $total_revenues = $order_query->sum('total_amount');

        $total_sales = $order_query->sum('quantity');

        $total_customers = $this->userRepository->getTotalCustomers($user, $filter);

        $new_orders_query = $this->orderRepository->queryRelation($user->orders(), [
            'start_date' => Carbon::now()->subDays(2), // 2 days ago
            'end_date' => now(),
        ]);

        $result = [
            'total_products' => $total_products,
            'total_sales' => $total_sales,
            'total_customers' => $total_customers,
            'total_revenues' => $total_revenues,
            'new_orders' => $new_orders_query->count(),
            'new_orders_revenue' => $new_orders_query->sum('total_amount'),
            'views' => 1,
        ];

        return new JsonResponse(['data' => $result]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Generates and returns a CSV file with user product records.
     *
     * @param  Request  $request  The HTTP request object containing filter parameters.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse A streamed response containing the CSV file.
     *
     * Request parameters:
     * - status (string): Enum of ProductStatusEnum. Used to filter products by status.
     * - start_date (string): Date in 'Y-m-d' format. Used along with end_date to filter records by date range.
     * - end_date (string): Date in 'Y-m-d' format. Used along with start_date to filter records by date range.
     * - format (string): Specifies the format for download, either 'csv' or 'pdf'. (Currently, only 'csv' is implemented).
     */
    public function records(Request $request)
    {
        $user = Auth::user();

        $filter = [
            'user_id' => $user->id,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $products = $this->productRepository->query($filter)->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $columns = ['Title', 'Price', 'Sales', 'Type', 'Status'];

        $data = [$columns];

        foreach ($products as $product) {
            $data[] = [
                $product->title,
                $product->price,
                $product->totalSales(),
                $product->product_type,
                $product->status,
            ];
        }

        $fileName = "products_$now.csv";

        $filePath = $this->generateCsv($fileName, $data);

        return $this->streamFile($filePath, $fileName, 'text/csv');
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve and export a list of products based on optional date filters.
     *
     * This method fetches products from the ProductRepository based on the provided
     * start and end dates, generates a CSV file with product details, and streams
     * the file as a response.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request containing optional start_date and end_date filters.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse Returns a streamed response with the CSV file.
     */
    public function adminRecords(Request $request)
    {
        $filter = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $products = $this->productRepository->find($filter);

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $columns = ['Title', 'Price', 'Sales', 'Type', 'Status'];

        $data = [$columns];

        foreach ($products as $product) {
            $data[] = [
                $product->title,
                $product->price,
                $product->totalSales(),
                $product->product_type,
                $product->status,
            ];
        }

        $fileName = "products_$now.csv";

        $filePath = $this->generateCsv($fileName, $data);

        return $this->streamFile($filePath, $fileName, 'text/csv');
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Toggles the publish status of a product.
     *
     * This method changes the status of a product between 'Draft' and 'Published'.
     * If the product is currently in 'Draft' status, it will be set to 'Published'.
     * If the product is currently in 'Published' status, it will be set to 'Draft'.
     * If the product has been deleted (soft-deleted), an exception will be thrown.
     *
     * It Notifies the Product owner of the published product
     *
     * @param  Product  $product  The product whose status is to be toggled.
     * @return ProductResource A resource containing the updated product.
     *
     * @throws BadRequestException If the product is deleted (trashed).
     */
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

        // Dispatch Product Publish Notification
        if ($product->status === ProductStatusEnum::Published->value) {
            $product->user->notify(new ProductPublished($product));
        }

        return new ProductResource($product);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Soft deletes a product.
     *
     * This method changes the status of a product to 'draft' and then soft deletes it.
     * Soft deleting means the product is not permanently removed from the database but is marked as deleted.
     *
     * @param  Product  $product  The product to be soft deleted.
     * @return ProductResource A resource containing the soft-deleted product.
     */
    public function delete(Product $product)
    {
        $product->status = 'draft';
        $product->save();
        $product->delete();

        return new ProductResource($product);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Restore a soft-deleted product.
     *
     * This method restores a product that was previously soft deleted. It uses route model binding
     * to inject the soft-deleted model into the controller function.
     *
     * @param  Product  $product  The soft-deleted product to be restored.
     * @return ProductResource A resource containing the restored product.
     *
     * @see \App\Providers\RouteServiceProvider
     * I had to bind the product to the route so Laravel could inject the soft-deleted model into the controller
     * function.
     * @see https://laracasts.com/discuss/channels/laravel/route-model-binding-with-soft-deleted-model?page=1&replyId=379334
     */
    public function restore(Product $product)
    {
        $product->restore();

        return new ProductResource($product);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Permanently delete a product.
     *
     * This method forcefully deletes a product from the database, removing it permanently.
     * Once deleted, the product cannot be restored.
     *
     * @param  Product  $product  The product to be permanently deleted.
     * @return \Illuminate\Http\Response A response indicating the product has been permanently deleted.
     */
    public function forceDelete(Product $product)
    {
        $product->forceDelete();

        return response('product is permanently deleted');
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get the list of products purchased by the authenticated user.
     *
     * This method retrieves all the products that the authenticated user has purchased and maps them to a simplified
     * array structure, including product details like ID, title, data, thumbnail, slug, publisher, and price.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of downloaded products.
     */
    public function purchased()
    {
        $user = Auth::user();

        $downloads = $user->purchases()->get();

        $products = $downloads->map(function ($download) {
            $product = $download->product;

            return [
                'id' => $product->id,
                'title' => $product->title,
                'thumbnail' => $product->thumbnail,
                'slug' => $product->slug,
                'publisher' => $product->user->full_name,
                'price' => $product->price,
            ];
        });

        return new JsonResponse($products);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get the top 5 products.
     *
     * This method retrieves the top products from the product repository, limits the results to the top 5 products,
     * and paginates the results.
     *
     * @return ProductCollection A collection of the top products, paginated.
     */
    public function topProducts()
    {
        $top_products = $this->productRepository->topProducts()->paginate(5);

        return ExternalProductResource::collection($top_products);
    }

    /**
     * Retrieve the best selling products based on optional date filters.
     *
     * This method fetches the top-selling products from the ProductRepository
     * based on the provided start and end dates, paginates the results, and
     * returns them as a collection of ProductResources.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request containing optional start_date and end_date filters.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Returns a collection of ProductResource.
     */
    public function bestSelling(Request $request)
    {
        $filter = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $top_products = $this->productRepository->topProducts($filter)->paginate(5);

        return ProductResource::collection($top_products);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get the top 5 products asscoiated with a user.
     *
     * This method retrieves the top products associated with a user from the product repository, limits the results to the top 5 products,
     * and paginates the results.
     *
     * @return ProductResource A collection of the top products, paginated.
     */
    public function getUserTopProducts()
    {
        $user = Auth::user();

        $top_products = $this->productRepository->topProducts(['products.user_id' => $user->id]);

        return ProductResource::collection($top_products
            ->limit(5)->paginate(5));
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get the revenue of the user's products over the past four weeks.
     *
     * This method calculates the total revenue generated from the user's products for the current week,
     * the previous week, two weeks ago, and three weeks ago. The revenue is calculated by summing the total
     * amount of orders within the specified date ranges.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the revenue data for the past four weeks.
     */
    public function productsRevenue()
    {
        $user = Auth::user();

        $relation = $user->orders();

        $today = Carbon::today();
        $lastWeekStart = $today->copy()->subWeek()->startOfWeek();
        $lastWeekEnd = $today->copy()->subWeek()->endOfWeek();
        $twoWeeksAgoStart = $today->copy()->subWeeks(2)->startOfWeek();
        $twoWeeksAgoEnd = $today->copy()->subWeeks(2)->endOfWeek();
        $threeWeeksAgoStart = $today->copy()->subWeeks(3)->startOfWeek();
        $threeWeeksAgoEnd = $today->copy()->subWeeks(3)->endOfWeek();

        $revForThisWeek = $this->orderRepository->queryRelation($relation, [
            'start_date' => $today->startOfWeek(),
            'end_date' => $today->endOfWeek(),
        ])->sum('total_amount');

        $revForLastWeek = $this->orderRepository->queryRelation($relation, [
            'start_date' => $lastWeekStart,
            'end_date' => $lastWeekEnd,
        ])->sum('total_amount');

        $revForTwoWeeksAgo = $this->orderRepository->queryRelation($relation, [
            'start_date' => $twoWeeksAgoStart,
            'end_date' => $twoWeeksAgoEnd,
        ])->sum('total_amount');

        $revForThreeWeeksAgo = $this->orderRepository->queryRelation($relation, [
            'start_date' => $threeWeeksAgoStart,
            'end_date' => $threeWeeksAgoEnd,
        ])->sum('total_amount');

        return new JsonResponse([
            'data' => [
                'revForThisWeek' => $revForThisWeek,
                'revForLastWeek' => $revForLastWeek,
                'revForTwoWeeksAgo' => $revForTwoWeeksAgo,
                'revForThreeWeeksAgo' => $revForThreeWeeksAgo,
            ],
        ]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get all available product tags.
     *
     * This method retrieves all available product tags from the ProductTagsEnum and returns them in a JSON response.
     *
     * @return JsonResponse A JSON response containing the list of available product tags.
     */
    public function tags()
    {
        $tags = ProductTagsEnum::cases();

        return new JsonResponse(['data' => $tags]);
    }

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Handles the search request for products.
     *
     * This method retrieves the search string from the request and queries the product repository
     * to find products that match the given string. The search includes:
     * - Matching the product title
     * - Matching the product description
     * - Matching tags within the JSON tags column
     * - Matching the full name of the associated user
     *
     * Results are returned in a collection of products, and the search results are stored
     * in a cookie.
     *
     * @param  SearchRequest  $request  The incoming search request.
     * @return ProductCollection The JSON response containing the collection of products matching the search criteria,
     *                           with a search results cookie.
     *
     * @see \App\Repositories\ProductRepository::search() The repository method used for querying the products.
     * @see \App\Models\Product scope methods for search query defined.
     */
    public function search(SearchRequest $request)
    {
        // Get the search string, defaulting to an empty string if null.
        $text = $request->input('text') ?? '';

        // Query the database.
        $products = $this->productRepository->search($text)->get();

        // Save the search results in cookie
        $cookie = cookie('search_term', json_encode($products->pluck('id')->toArray()), 60);

        return ExternalProductResource::collection($products)->response()->cookie($cookie);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Suggest products to the user based on their previous searches.
     *
     * This method retrieves the currently authenticated user via Sanctum,
     * then fetches the last 10 products they have searched for using the
     * product repository. It returns these products as a collection.
     *
     * @param  Request  $request  The incoming request instance.
     * @return ProductCollection A collection of products based on the user's previous searches.
     */
    public function basedOnSearch(Request $request)
    {
        $products = [];

        $user = $request->user('sanctum');

        if ($user) {
            $products = $this->productRepository->findSearches($user);
        }

        return ExternalProductResource::collection($products);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get statistical data about products, orders, and customers.
     *
     * @return JsonResource
     */
    public function stats()
    {
        $order_query = $this->orderRepository->query([]);

        $total_products = $this->productRepository->query([])->count();

        $total_sales = $order_query->sum('quantity');

        $total_customers = $this->customerRepository->query([])->count();

        $total_revenue = $order_query->sum('total_amount');

        return new JsonResource([
            'total_products' => $total_products,
            'total_sales' => $total_sales,
            'total_customers' => $total_customers,
            'total_revenue' => $total_revenue,
        ]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Send a congratulations email for the best-selling product to product owner.
     *
     * @param  Product  $product  The best selling product
     * @return JsonResource
     */
    public function sendCongratulations(Product $product)
    {
        Mail::send(new BestSellerCongratulations($product));

        return new JsonResource([
            'message' => 'Email sent',
        ]);
    }

    public function types()
    {
        $types = [
            [
                'name' => ProductEnum::DIGITAL_PRODUCT->value,
                'categories' => DigitalProductCategory::cases(),
            ],
            [
                'name' => ProductEnum::SKILL_SELLING->value,
                'categories' => SkillSellingCategory::cases(),
            ],
        ];

        return new JsonResource($types);
    }
}
