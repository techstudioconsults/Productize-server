<?php

namespace App\Jobs;

use App\Models\Funnel;
use App\Models\FunnelCampaign;
use App\Services\EmailMarketingProviders\EmailMarketingFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateFunnelCampaignList implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Funnel $funnel,
    ) {}

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->funnel->user->emailMarkertings as $emailMarketing) {

            $data = [
                'token' => $emailMarketing->token,
                'provider' => $emailMarketing->provider,
                'name' => $this->funnel->slug,
            ];

            $id = EmailMarketingFactory::createCampaign($data);

            FunnelCampaign::create([
                'funnel_id' => $this->funnel->id,
                'provider' => $emailMarketing->provider,
                'list_id' => $id,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::critical('Email Marketing Campaign on Funnel Create Threw An Error', [
            'context' => $exception->getMessage(),
        ]);

        $this->fail($exception);
    }
}
