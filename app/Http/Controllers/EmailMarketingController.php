<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailMarketing\UploadTokenRequest;
use App\Repositories\EmailMarketingRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailMarketingController extends Controller
{
    public function __construct(
        protected EmailMarketingRepository $emailMarketingRepository
    ) {}

    public function token(UploadTokenRequest $request)
    {
        $payload = $request->validated();
        $payload['user_id'] = $request->user()->id;

        $this->emailMarketingRepository->create($payload);

        return new JsonResource(['message' => 'success']);
    }
}
