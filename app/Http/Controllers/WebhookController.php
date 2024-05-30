<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerErrorException;
use App\Repositories\PaystackRepository;
use App\Repositories\WebhookRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookRepository $webhookRepository,
        protected PaystackRepository $paystackRepository
    )
    {

    }
    public function paystack(Request $request)
    {
        $payload = $request->getContent();

        $paystackHeader = $request->header('x-paystack-signature');

        if ($this->webhookRepository->paystack($payload, $paystackHeader)) {

            try {
                $data = json_decode($payload, true);

                Log::channel('webhook')->info('data', ['value' => $data['data']]);
                Log::channel('webhook')->info('event', ['value' => $data['event']]);

                $this->webhookRepository->paystack($data['event'], $data['data']);
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }

            return response('webhook success', 200);
        } else {
            Log::critical('message', ['error' => 'Invalid webhook signature']);
        }
    }
}
