<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */


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
     * @author @obajide028 Odesanya Babajide
     *
     * Retrieves a paginated list of user's faqs.
     *
     * @return \App\Http\Resources\FaqResource Returns a paginated collection of a user faqs.
     */
    public function index()
    {
        $faq = $this->faqRepository->find();
        return FaqResource::collection($faq);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     * 
     * Store a newly created resource in storage.
     * @param StoreFaqRequest $request 
     * 
     * creates a new faq
     */
    public function store(StoreFaqRequest $request)
    {
        $faq = $this->faqRepository->create($request->validated());
        return response()->json(new FaqResource($faq), 201);
    }


    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Update a given faq.
     *
     * @param  \App\Http\Requests\UpdateFaqRequest  $request The incoming request containing validated faq update data.
     * @param  \App\Models\Faq  $cfaq The faq to be updated.
     * @return \App\Http\Resources\FaqResource Returns a resource representing the newly updated faq.
     */
    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $faq = $this->faqRepository->update($faq, $request->validated());

        return new FaqResource($faq);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Delete a given faq.
     *
     * @param  \App\Models\Faq  $faq The faq to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a resource with a confirmation message.
     */
    public function delete(Faq $faq)
    {
        $this->faqRepository->deleteOne($faq);
        return response()->json(['Message' => 'FAQ deleted successfully']);
    }
}
