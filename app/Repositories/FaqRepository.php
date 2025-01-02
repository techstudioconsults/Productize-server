<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 09-05-2024
 */

namespace App\Repositories;

use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author @obajide028 Odesanya Babajide
 *
 * Repository for Faq resource
 */
class FaqRepository extends Repository
{
    public function seed(): void
    {
        Faq::factory()->count(10)->create();
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * create new faq.
     *
     * array with params:
     *
     * @param  array  $entity  The data for creating a new faq
     * @return Faq the newly created cfaq instance
     */
    public function create(array $entity): Faq
    {
        if (! isset($entity['title'])) {
            throw new BadRequestException('No title Provided');
        }

        if (! isset($entity['answer'])) {
            throw new BadRequestException('No answer Provided');
        }

        if (! isset($entity['question'])) {
            throw new BadRequestException('No question Provided');
        }

        return Faq::create($entity);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Query faqs based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply
     * @return Builder The query builder for faqs.
     */
    public function query(array $filter): Builder
    {
        $query = Faq::query();

        // apply date filter
        $this->applyDateFilters($query, $filter);

        // apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author  @obajide028 Odesanya Babajide
     *
     * Find faqs based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found faqs.
     */
    public function find(?array $filter = null): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Find a faq by its ID.
     *
     * @param  string  $id  The ID of the faq to find.
     * @return Faq|null The found faq instance, or null if not found.
     */
    public function findById(string $id): ?Faq
    {
        return Faq::find($id);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Find a single faq based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Faq|null The found cart instance, or null if not found.
     */
    public function findOne(array $filter): ?Faq
    {
        return $this->query($filter)->first();
    }

    /**
     * @author  @obajide028 Odesanya Babajide
     *
     * Update a faq entity with the provided updates.
     *
     * @param  Model  $entity  The faq entity to update.
     * @param  array  $updates  The updates to apply to the faq.
     * @return Faq The updated faq instance.
     *
     * @throws ModelCastException If the provided entity is not a faq instance.
     */
    public function update(Model $entity, array $updates): Faq
    {
        if (! $entity instanceof Faq) {
            throw new ModelCastException('Faq', get_class($entity));
        }

        // Assign the updates to the corresponding fields of the User instance
        // It ignores keys passed but not present in model columns
        $entity->fill($updates);

        // Save the updated faq instance
        $entity->save();

        // Return the updated faq model
        return $entity;
    }

    /**
     *  array with params:
     */
    public function delete(FaqResource $faqResource): void
    {
        $faqArray = $faqResource->toArray(request());
        $faq = Faq::where($faqArray)->firstOrFail();
        $faq->delete();
    }
}
