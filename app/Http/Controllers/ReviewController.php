<?php
/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 22-05-2024
 */

namespace App\Http\Controllers;

use App\Exceptions\ConflictException;
use App\Models\Review;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Repositories\ReviewRepository;
use Auth;

class ReviewController extends Controller
{

    public function __construct(protected ReviewRepository $reviewRepository)
    {
        
    }
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
     * @param StoreReviewRequest $request 
     * 
     * creates a new review
     */
    public function store(StoreReviewRequest $request, $productId)
    {
        // Retrieve the authenticated user
        $user = Auth::user();
    
        // Validate the request and get the validated data
        $payload = $request->validated();
    

          // Find the product or throw an exception if it doesn't exist
         $product = Product::findOrFail($productId);

        // Add the user ID to the payload
        $payload['user_id'] = $user->id;
    
        // Find the product
        $payload['product_id'] = $product->id;
    
        // Check if the review already exists
        $exist = $user->reviews()->where('product_id', $payload['product_id'])->first();
    
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
     * It returns the first 3 in the collection.
     *
     * @param Product $product The product for which to retrieve orders.
     * @return \App\Http\Resources\ReviewResource A collection of review resources.
     */
    public function findByProduct($productId)
    {
        $filter = ['product_id' =>$productId];
        
        $reviews = $this->reviewRepository->query($filter)->take(2)->get();

        return ReviewResource::collection($reviews);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReviewRequest $request, Review $review)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $review)
    {
        //
    }
}
