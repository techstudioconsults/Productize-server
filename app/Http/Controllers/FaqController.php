<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Repositories\FaqRepository;
use App\Http\Resources\FaqResource;

class FaqController extends Controller
{
    public function __construct(
        protected FaqRepository $faqRepository
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faq = $this->faqRepository->getAll();
        return FaqResource::collection($faq);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFaqRequest $request)
    {
        $faq = $this->faqRepository->create($request->all());
        return response()->json(new FaqResource($faq), 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $faq = $this->faqRepository->update($faq->id, $request->all());
        return new FaqResource($faq);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faq $faq)
    {
        $this->faqRepository->delete($faq->id);
        return response()->json(['Message => FAQ deleted successfuly']);
    }
}
