<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 
 */

namespace App\Repositories;

use App\Http\Resources\FaqResource;
use App\Http\Resources\ReviewResource;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Review;

class ReviewRepository
{
    

    public function create(array $array)
    {
        $review =  Review::create($array);

        return $review;
    }


    public function findByProduct(Product $productId)
    {
      return Review::where('product_id', $productId)->with('user')->get();
    }


    public function findById(Review $review)
    {
        return Review::findOrFail($review->id);
    }

    


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
