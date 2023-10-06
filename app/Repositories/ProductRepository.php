<?php

namespace App\Repositories;

use App\Events\Products;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductRepository
{
    /**
     * @Intuneteq
     *
     * @param credentials {array} - All products properties except uploadables e.g thumbanails, cover photos etc.
     * @param thumbnail {mixed} - Uploaded thumbnail file object. Must be an Image
     * @param data {mixed} - uploaded Product file object
     * @param cover_photos {array} - Array of image file objects. Must be an array of Image.
     *
     * @uses App\Events\Products
     * 
     * @return Product
     *
     * For more details, see {@link \App\Http\Requests\StoreProductRequest}.
     */
    public function create(array $credentials, mixed $thumbnail, mixed $data, array $cover_photos)
    {
        $thumbnail = $this->uploadThumbnail($thumbnail);

        $cover_photos = $this->uploadCoverPhoto($cover_photos);

        $data = $this->uploadData($data);

        $credentials['data'] = $data;
        $credentials['cover_photos'] = $cover_photos;
        $credentials['thumbnail'] = $thumbnail;

        $product = Product::create($credentials);

        event(new Products($product));

        return $product;
    }

    private function uploadData(mixed $data)
    {
        $uploadedData = [];

        foreach ($data as $item => $file) {
            $originalName = str_replace(' ', '', $file->getClientOriginalName());

            $path = Storage::putFileAs('digital-products', $file, $originalName);

            $url = config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path;

            array_push($uploadedData, $url);
        }

        return $uploadedData;
    }

    private function uploadCoverPhoto(mixed $cover_photos)
    {
        $uploadedCoverPhotos = [];
        foreach ($cover_photos as $item => $file) {
            $originalName = str_replace(' ', '', $file->getClientOriginalName());

            $path = Storage::putFileAs('products-cover-photos', $file, $originalName);

            $url = config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path;

            array_push($uploadedCoverPhotos, $url);
        }

        return $uploadedCoverPhotos;
    }

    private function uploadThumbnail($thumbnail)
    {
        $thumbnailPath = Storage::putFileAs(
            'products-thumbnail',
            $thumbnail,
            str_replace(' ', '', $thumbnail->getClientOriginalName())
        );

        return config('filesystems.disks.spaces.cdn_endpoint') . '/' . $thumbnailPath;
    }
}
