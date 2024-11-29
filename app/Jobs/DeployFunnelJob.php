<?php

namespace App\Jobs;

use App\Enums\ProductStatusEnum;
use App\Events\FunnelPublished;
use App\Exceptions\ServerErrorException;
use App\Models\Funnel;
use Artisan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class DeployFunnelJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Funnel $funnel,
    ) {}

     /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->funnel->id;
    }

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
        if (env('APP_ENV') === 'local') {
            return;
        }

        try {
            Artisan::call('deploy:funnel', ['page' => $this->funnel->slug]);
        } catch (\Throwable $th) {
            Log::debug('Deploy Publish error', ['context' => $th->getMessage()]);
        }

        // save funnel status to published
        $this->funnel->status = ProductStatusEnum::Published->value;
        $this->funnel->save();

        FunnelPublished::dispatch($this->funnel);
    }
}
