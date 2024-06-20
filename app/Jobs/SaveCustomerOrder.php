<?php

namespace App\Jobs;

use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class SaveCustomerOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    protected $email;

    protected $reference_no;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $reference_no,
        $data,
        $email,
    ) {
        // $this->onQueue('order');
        $this->data = $data;
        $this->email = $email;
        $this->reference_no = $reference_no;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('webhook')->info('I was dispatched');

        Log::channel('webhook')->info('did i get here', ['customer' => $this->reference_no, 'email' => $this->email]);
        // $customerRepository = App::make(CustomerRepository::class);
        // $orderRepository = App::make(OrderRepository::class);

        try {
            // Update user customer list for each product
            foreach ($this->data['products'] as $product) {
                // Log::channel('webhook')->critical('what is product', ['product' => $product]);

                // $customer = $customerRepository->createOrUpdate($this->email, $product['product_slug']);

                $buildOrder = [
                    'reference_no' => $this->reference_no,
                    // 'product_id' => $customer->latest_puchase_id,
                    // 'customer_id' => $customer->id
                ];

                // $orderRepository->create($buildOrder);
            }
        } catch (\Throwable $th) {
            Log::channel('webhook')->critical('ERROR OCCURED', ['error' => $th->getMessage()]);
        }
    }
}
