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
     * Display a listing of the resource.
     */
    public function index()
    {
        $faq = $this->faqRepository->findAll();
        return FaqResource::collection($faq);
    }

    /**
     * Store a newly created resource in storage.
     * @param StoreFaqRequest $request 
     * 
     * creates a new faq $request The HTTP request containing query parameters:
     *                         - title (required)
     *                         - question (required)
     *                         - answer (required)
     * 
     */
    public function store(StoreFaqRequest $request)
    {
        $faq = $this->faqRepository->create($request->validated());
        return response()->json(new FaqResource($faq), 201);
    }


    /**
     * Update the specified resource in storage.
     * @param UpdateFaqRequest $request The HTTP request containing query parameters:
     *                         - title (required)
     *                         - question (required)
     *                         - answer (required)
     * @return  
     */
    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $faqResource = new FaqResource($faq);
        $updatedFaq = $this->faqRepository->update($faqResource, $request->validated());
        return response()->json(new FaqResource($updatedFaq), 200);
    }

/**
 * Remove the specified resource from storage.
 *
 * @param Faq $faq - The Faq model instance to delete
 */
public function destroy(Faq $faq)
{
    $faqResource = new FaqResource($faq);
    $this->faqRepository->delete($faqResource);
    return response()->json(['Message' => 'FAQ deleted successfully']);
}
}
