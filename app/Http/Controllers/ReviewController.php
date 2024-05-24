<?php

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
     * Display a listing of the resource.
     */
    public function index()
    {
       $reviews = $this->reviewRepository->findAll();
       return ReviewResource::collection($reviews);
    }
    
    /**
     * Store a newly created resource in storage.
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

    public function getReviewsByProduct($productId)
    {
        $reviews = $this->reviewRepository->findByProduct($productId);

        return (ReviewResource::collection($reviews));
    }
}
