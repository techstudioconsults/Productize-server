<?php

namespace App\Jobs;

use App\Models\EmailMarketing;
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

class FunnelCampaignSubscriber implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Funnel $funnel,
        public array $subscriber // email, fullname => first_name, last_name
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
        $campaigns = FunnelCampaign::where(['funnel_id' => $this->funnel->id])->get();

        foreach ($campaigns as $campaign) {
            $em = EmailMarketing::where(['user_id' => $this->funnel->user->id, 'provider' => $campaign->provider])->first();

            $data = [
                'subscriber' => $this->subscriber,
                'provider' => $campaign->provider,
                'token' => $em->token,
                'campaign_id' => $campaign->list_id,
            ];

            EmailMarketingFactory::addSubscriber($data); // data, provider, token, campain_id
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::critical('Email Marketing Campaign on Funnel Create Threw An Error', [
            'context' => $exception->getMessage(),
        ]);

        $this->fail($exception);
    }
}
