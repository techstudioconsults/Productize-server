<?php

namespace App\Repositories;

use App\Enums\ProductStatusEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Jobs\DeployFunnelJob;
use App\Jobs\DropFunnelJob;
use App\Models\Funnel;
use App\Traits\HasStatusFilter;
use Artisan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Log;
use Storage;

/**
 * @author Tobi Olanitori
 *
 * @version 1.0
 *
 * Repository for Funnel resource
 */
class FunnelRepository extends Repository
{
    use HasStatusFilter;

    const THUMBNAIL_PATH = 'funnels-thumbnail';

    const ASSET_PATH = 'funnels-asset';

    /**
     * Create a new funnel with the provided entity.
     *
     * @param  array  $entity  The funnel data.
     * @return Funnel The newly created funnel.
     */
    public function create(array $entity): Funnel
    {
        return Funnel::create($entity);
    }

    /**
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
     * Update an entity in the database.
     *
     * @param  Model  $entity  The funnel to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return Funnel The updated funnel
     */
    public function update(Model $entity, array $updates): Funnel
    {
        // Ensure that the provided entity is an instance of Funnel
        if (! $entity instanceof Funnel) {
            throw new ModelCastException('Funnel', get_class($entity));
        }

        $entity->update($updates);

        return $entity;
    }

    /**
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

    public function uploadAsset(object $asset): string
    {
        // Each item in the 'data' array must be a file
        if (! $this->isValidated([$asset], ['required|file'])) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        $thumbnailPath = Storage::putFileAs(
            self::ASSET_PATH,
            $asset,
            str_replace(' ', '', $asset->getClientOriginalName())
        );

        return config('filesystems.disks.spaces.cdn_endpoint') . '/' . $thumbnailPath;
    }

    /**
     * Publishes the given funnel by triggering the deployment command and updating its status.
     *
     * This method performs the following:
     * - Skips the deployment command if the application environment is local.
     * - Executes the `deploy:funnel` Artisan command with the funnel's slug as the `page` argument.
     * - Catches any exceptions during deployment, rethrowing them as `ServerErrorException` for centralized error handling.
     * - Updates the funnel’s status to `Published` and saves the change.
     *
     * @param  Funnel  $funnel  The funnel to publish.
     * @return void
     *
     * @throws ServerErrorException If the deployment command fails due to any error.
     */
    public function publish(Funnel $funnel)
    {
        DeployFunnelJob::dispatch($funnel);
    }

    /**
     * Drop the specified funnel and update its status to draft.
     *
     * This method triggers the `drop:funnel` Artisan command to drop a funnel's resources
     * based on its slug. In a local environment, the command is skipped.
     *
     * @param  Funnel  $funnel  The funnel instance to be dropped.
     * @return void
     *
     * @throws ServerErrorException If an error occurs while calling the Artisan command.
     */
    public function drop(Funnel $funnel)
    {
        DropFunnelJob::dispatch($funnel);
    }

    /**
     * Generate and save a funnel template as an HTML file.
     *
     * This method renders an HTML view using the specified template data
     * and saves the generated HTML file to local storage with a filename based on the funnel's slug.
     *
     * @param  Funnel  $funnel  The funnel instance for which the template is being saved.
     * @param  string  $template  The template content to be rendered and saved.
     * @return void
     */
    public function saveTemplate(Funnel $funnel, string $template)
    {
        // Create the template html file
        $html = view('funnels.template', ['template' => $template])->render();

        // Save the template locally
        Storage::disk('local')->put('funnels/' . $funnel->slug . '.html', $html);
    }
}
