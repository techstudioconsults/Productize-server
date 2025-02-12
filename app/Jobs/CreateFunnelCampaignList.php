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
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->funnel->user->emailMarkertings as $emailMarketing) {
            $data = [
                'token' => $emailMarketing->token,
                'provider' => $emailMarketing->provider,
                'name' => $this->funnel->name,
            ];

            $id = EmailMarketingFactory::createCampaign($data);

            FunnelCampaign::create([
                'funnel_id' => $this->funnel->id,
                'provider' => $emailMarketing->provider,
                'list_id' => $id,
            ]);
        }
    }
}
