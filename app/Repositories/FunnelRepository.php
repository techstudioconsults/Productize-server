<?php

namespace App\Repositories;

use App\Enums\ProductStatusEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Models\Funnel;
use App\Traits\HasStatusFilter;
use Artisan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Storage;

/**
 * @author
 *
 * @version 1.0
 *
 * @since
 *
 * Repository for Funnel resource
 */
class FunnelRepository extends Repository
{
    use HasStatusFilter;

    const THUMBNAIL_PATH = 'funnels-thumbnail';

    /**
     * @author
     *
     * Create a new funnel with the provided entity.
     *
     * @param  array  $entity  The funnel data.
     *
     * @return Funnel The newly created funnel.
     */
    public function create(array $entity): Funnel
    {
        return Funnel::create($entity);
    }

    /**
     * @author
     *
     * Query funnel based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for funnels.
     */
    public function query(array $filter): Builder
    {
        $query = Funnel::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        $this->applyStatusFilter($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author
     *
     * Find funnels based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found funnels.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author
     *
     * Find a funnel by their ID.
     *
     * @param  string  $id  The ID of the funnel to find.
     * @return Funnel|null The found funnel instance, or null if not found.
     */
    public function findById(string $id): ?Funnel
    {
        return Funnel::find($id);
    }

    /**
     * @author
     *
     * Find a single funnel based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Funnel|null The found funnel instance, or null if not found.
     */
    public function findOne(array $filter): ?Funnel
    {
        return Funnel::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author
     *
     * Update an entity in the database.
     *
     * @param  Model  $entity  The funnel to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return Funnel The updated funnel
     */
    public function update(Model $entity, array $updates): Funnel
    {
        // Ensure that the provided entity is an instance of Funnel
        if (!$entity instanceof Funnel) {
            throw new ModelCastException('Funnel', get_class($entity));
        }

        $entity->update($updates);
        return $entity;
    }


    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Uploads a funnels's thumbnail image and returns its storage path.
     *
     * @param  object  $thumbnail  The thumbnail image file to upload.
     * @return string The storage path of the uploaded thumbnail.
     *
     * @throws BadRequestException If the provided thumbnail fails validation.
     */
    public function uploadThumbnail(object $thumbnail): string
    {
        // Each item in the 'data' array must be a file
        if (! $this->isValidated([$thumbnail], ['required|image'])) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        $thumbnailPath = Storage::putFileAs(
            self::THUMBNAIL_PATH,
            $thumbnail,
            str_replace(' ', '', $thumbnail->getClientOriginalName())
        );

        return config('filesystems.disks.spaces.cdn_endpoint') . '/' . $thumbnailPath;
    }

    public function publish(Funnel $funnel)
    {
        // dont call artisan command in local env
        if (env('APP_ENV') === 'local') {
            return;
        }

        try {
            Artisan::call('deploy:funnel', ['page' => $funnel->slug]);
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }

        // save funnel status to published
        $funnel->status = ProductStatusEnum::Published->value;
        $funnel->save();
    }

    public function drop(Funnel $funnel)
    {
        // dont call artisan command in local env
        if (env('APP_ENV') === 'local') {
            return;
        }

        try {
            Artisan::call('drop:funnel', ['page' => $funnel->slug]);
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }

        // save funnel status to published
        $funnel->status = ProductStatusEnum::Draft->value;
        $funnel->save();
    }

    public function saveTemplate(Funnel $funnel, string $template)
    {
        // Create the template html file
        $html = view('funnels.template', ['template' => $template])->render();

        // Save the template locally
        Storage::disk('local')->put('funnels/' . $funnel->slug . '.html', $html);
    }
}
