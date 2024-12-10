<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 22-05-2024
 */

namespace App\Http\Controllers;

use App\Exceptions\ConflictException;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\UserRepository;
use Auth;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewRepository $reviewRepository,
        protected ProductRepository $productRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Retrieves a paginated list of a user's review.
     *
     * @return \App\Http\Resources\ReviewResource Returns a paginated collection of a user reviews.
     */
    public function index()
    {
        $reviews = $this->reviewRepository->find();

        return ReviewResource::collection($reviews);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Store a newly created resource in storage.
     *
     * @param  Product  $product
     *
     * creates a new review
     */
    public function store(StoreReviewRequest $request, Product $product)
    {
        // Retrieve the authenticated user
        $user = Auth::user();

        // Validate the request and get the validated data
        $payload = $request->validated();

        // Add the user ID to the payload
        $payload['user_id'] = $user->id;

        // Add the product to the payload
        $payload['product_id'] = $product->id;

        // Check if the review already exists
        $exist = $this->reviewRepository->query([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ])->exists();

        if ($exist) {
            throw new ConflictException('You have already reviewed this product.');
        }

        // Create the review
        $review = $this->reviewRepository->create($payload);

        // Return the created review
        return response()->json(new ReviewResource($review), 201);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Retrieve a collection of reviews associated with a specific product.
     *
     * It returns the first 2 in the collection.
     *
     * @param  Product  $product  The product for which to retrieve orders.
     * @return \App\Http\Resources\ReviewResource A collection of review resources.
     */
    public function findByProduct(Product $product)
    {
        $filter = ['product_id' => $product->id];

        $reviews = $this->reviewRepository->query($filter)
            ->with('user:id,full_name,logo')
            ->take(2)->get();

        return ReviewResource::collection($reviews);

    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Retrieve the average Rating of a product.
     *
     * =
     *
     * @param  Product  $product  The product for which to retrieve the average rating.
     * @return the response in json format
     */
    public function getAverageRatingForProduct(Product $product)
    {
        $averageRating = $this->reviewRepository->getAverageRatingForProduct($product);

        return response()->json([
            'averageRating' => $averageRating,
        ]);
    }
}
