<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 22-05-2024
 */

namespace App\Repositories;

use App\Http\Resources\FaqResource;
use App\Http\Resources\ReviewResource;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Review;

class ReviewRepository
{

    /**
     *  array with params:
     * @param comment
     * @param rating
     * @param productId
     * @param userId
     * 
     */

    public function create(array $array)
    {
        $review =  Review::create($array);

        return $review;
    }

    /**
     *  array with params:
     * @param productId
     * 
     */

    public function findByProduct($productId)
    {
        return Review::where('product_id', $productId)->with('user')->get();
    }

    public function findAll()
    {
        $review = Review::all();

        return $review;
    }

    /**
     *  array with params:
     * @param Review $request
     * 
     */

    public function findById(Review $review)
    {
        return Review::findOrFail($review->id);
    }


    /**
     *  array with params:
     * @param comment
     * @param rating
     * @param productId
     * @param userId
     * 
     * 
     * @param ReviewResource $reviewResource
     * 
     */


    public function update(ReviewResource $reviewResource, array $array): Review
    {
        $reviewArray = $reviewResource->toArray(request());
        $review = Review::where($reviewArray)->firstOrFail();
        $review->update($array);
        return $review;
    }


    public function delete(ReviewResource $reviewResource): void
    {
        $reviewArray = $reviewResource->toArray(request());
        $review = Review::where($reviewArray)->firstOrFail();
        $review->delete();
    }
}
