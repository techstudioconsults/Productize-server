<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use App\Repositories\FaqRepository;
// use Illuminate\Http\Request;

class FaqController extends Controller

{
  public function __construct(
    protected FaqRepository $faqRepository
  ) {
  }

  // Show all Faqs
  public function index()
  {
    $faq = $this->faqRepository->getAll();
    return FaqResource::collection($faq);
  }


  public function store(FaqRequest $request)
  {
    $faq = $this->faqRepository->create($request->all());
    return new FaqResource($faq);
  }
}
