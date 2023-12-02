<?php

namespace App\Http\Resources;

use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($product) {
                // return $this->productRepository->getProductExternal($product);
                return [
                    'title' => $product->title,
                    'thumbnail' => $product->thumbnail,
                    'price' => $product->price,
                    'publisher' => $product->user->full_name,
                    'slug' => $product->slug,
                    'highlights' => $this->highlights,
                    'product_type' => $this->product_type,
                    'cover_photos' => $this->cover_photos,
                    'tags' => $this->tags,
                    'description' => $this->description,
                ];
            }),
        ];
    }
}
