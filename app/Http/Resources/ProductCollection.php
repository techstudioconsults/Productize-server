<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

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
                $data = $product->data->getArrayCopy();
                $dataInfo = Arr::only($data, ['size', 'format']);
                
                return [
                    'title' => $product->title,
                    'thumbnail' => $product->thumbnail,
                    'price' => $product->price,
                    'publisher' => $product->user->full_name,
                    'slug' => $product->slug,
                    'highlights' => $product->highlights,
                    'product_type' => $product->product_type,
                    'cover_photos' => $product->cover_photos,
                    'tags' => $product->tags,
                    'description' => $product->description,
                    'data_info' => $dataInfo
                ];
            }),
        ];
    }
}
