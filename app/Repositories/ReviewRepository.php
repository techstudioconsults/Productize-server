<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 22-05-2024
 */

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Http\Resources\FaqResource;
use App\Http\Resources\ReviewResource;
use App\Models\Community;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Svg\Tag\Rect;

/**
 * @author @obajide028 Odesanya Babajide
 *
 * Repository for Review resource
 */

class ReviewRepository extends Repository
{

    public function seed(): void
    {
        Review::factory()->create()->count(10);
    }


    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Create a new review.
     *
     * @param array $entity The data for creating the review.
     * @return Review The newly created review instance.
     */
    public function create(array $entity): Review
    {
        return Review::create($entity);
    }

     /**
     * @author @obajide028 Odesanya 
     *
     * Find reviews based on the provided filter.
     *
     * @param array|null $filter The filter criteria to apply (optional).
     * @return Collection The collection of found review.
     */
    public function find(?array $filter = null): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

     /**
     * @author @obajide028 Odesanya Babajide
     *
     * Find a reviews by its ID.
     *
     * @param string $id The ID of the order to find.
     * @return Review|null The found review instance, or null if not found.
     */
    public function findById(string $id): ?Review
    {
        return Review::find($id);
    }

    /**
     * @author @Obajide028 Odesanya babajide
     *
     * Find a single review based on the provided filter.
     *
     * @param array $filter The filter criteria to apply.
     * @return Review|null The found review instance, or null if not found.
     */
    public function findOne(array $filter): ?Review
    {
        return $this->query($filter)->first();
    }

     /**
     * @author @obajide028 Odesanya Babajide
     *
     * Query review based on the provided filter.
     *
     * @param array $filter The filter criteria to apply.
     * @return Builder The query builder for review.
     */
    public function query(array $filter): Builder
    {
        $query = Review::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        //Filter by product title
        if(isset($filter['product_title'])){
            $product_title = $filter['product_title'];

            //remove product title from the filter array
            unset($filter['product_title']);

            $query->whereHas('product', function(Builder  $productQuery) use ($product_title) {
                $productQuery->where('title', 'like', '%' . $product_title . '%');
            });
        }

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    
    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Update an entity in the database.
     *
     * @param  Model $entity The review to be updated
     * @param array $updates The array of data containing the fields to be updated.
     * @return Model The updated review
     */
    public function update(Model $entity, array $updates): Review
    {
        // Ensure that the provided entity is an instance of Review
        if (!$entity instanceof Review) {
            throw new ModelCastException("Review", get_class($entity));
        }

          // Assign the updates to the corresponding fields of the Review instance
          $entity->fill($updates);

          // Save the updated Review instance
          $entity->save();
  
          // Return the updated Review model
          return $entity;
    }
}
