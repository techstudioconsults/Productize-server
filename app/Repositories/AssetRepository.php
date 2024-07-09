<?php

namespace App\Repositories;

use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Storage;
use Str;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 06-07-2024
 *
 * Repository for Asset resource
 */
class AssetRepository extends Repository
{
    const PRODUCT_DATA_PATH = 'assets';

    /**
     * @author
     *
     * Create a new asset with the provided entity.
     *
     * @param  array  $entity  The asset data.
     * @return Asset The newly created asset.
     */
    public function create(array $entity): Asset
    {
        return Asset::create($entity);
    }

    /**
     * @author
     *
     * Query asset based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for assets.
     */
    public function query(array $filter): Builder
    {
        $query = Asset::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author
     *
     * Find assets based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found assets.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author
     *
     * Find a asset by their ID.
     *
     * @param  string  $id  The ID of the asset to find.
     * @return Asset|null The found asset instance, or null if not found.
     */
    public function findById(string $id): ?Asset
    {
        return Asset::find($id);
    }

    /**
     * @author
     *
     * Find a single asset based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Asset|null The found asset instance, or null if not found.
     */
    public function findOne(array $filter): ?Asset
    {
        return Asset::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author
     *
     * Update an entity in the database.
     *
     * @param  Model  $entity  The asset to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return Asset The updated asset
     */
    public function update(Model $entity, array $updates): Asset
    {
        // Ensure that the provided entity is an instance of Asset
        if (! $entity instanceof Asset) {
            throw new ModelCastException('Asset', get_class($entity));
        }

        $entity->update($updates);

        return $entity;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Uploads an array of the product's data files and returns their storage paths.
     *
     * The data is the actual resource being sold.
     *
     * @param  array  $data  An array of data files to upload.
     * @return array An array containing the storage paths of the uploaded data files.
     *
     * @throws BadRequestException If any of the provided data files fail validation.
     */
    public function uploadAssets($data, string $product_type): array
    {
        // Each item in the 'data' array must be a file
        if (! $this->isValidated($data, ['required|file'])) {
            throw new BadRequestException($this->getValidator()->errors()->first());
        }

        return collect($data)->map(function ($file) use ($product_type) {
            $name = Str::uuid().'.'.$file->extension(); // geneate a uuid

            $path = Storage::putFileAs("$product_type/".self::PRODUCT_DATA_PATH, $file, $name);

            return [
                'name' => str_replace(' ', '', $file->getClientOriginalName()),
                'url' => config('filesystems.disks.spaces.cdn_endpoint').'/'.$path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->extension(),
            ];
        })->all();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve file metadata for a given file path.
     *
     * @param  string  $filePath  The path of the file.
     * @return array|null An array containing file metadata including size and MIME type, or null if the file doesn't exist.
     */
    public function getFileMetaData(string $filePath)
    {
        if (Storage::disk('spaces')->exists($filePath)) {
            $size = Storage::size($filePath);
            $mime_Type = Storage::mimeType($filePath);

            return [
                'size' => round($size / 1048576, 2).'MB', // Convert byte to MB
                'mime_type' => $mime_Type,
            ];
        } else {
            return null;
        }
    }
}
