<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {
    }

    public function index()
    {
        $user = Auth::user();

        $products = Product::where('user_id', $user->id)->get();

        return ProductResource::collection($products);
    }


    public function store(StoreProductRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $validated['user_id'] = $user->id;

        // Aissign the product data to a variable
        $data = $validated['data'];
        $cover_photos = $validated['cover_photos'];
        $thumbnail = $validated['thumbnail'];

        // Take out the uploadables from the validated array to allow for mass assignment
        unset($validated['data']);
        unset($validated['cover_photos']);
        unset($validated['thumbnail']);

        $product = $this->productRepository->create(
            $validated,
            $thumbnail,
            $data, // The digital products
            $cover_photos
        );

        return new ProductResource($product);
    }


    public function show(Product $product)
    {
        //
    }


    public function update(UpdateProductRequest $request, Product $product)
    {
        //
    }


    public function destroy(Product $product)
    {
        //
    }
}
