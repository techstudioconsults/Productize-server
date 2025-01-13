<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\EmailMarketingProviders\EmailMarketingFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddToFunnelSubscribers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public array $data
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->user->emailMarkertings as $emailMarketing) {
            EmailMarketingFactory::addSubscriber(
                $this->data['email'],
                $this->data['fullname'],
                $emailMarketing->token,
                $emailMarketing->provider
            );
        }
    }
}
