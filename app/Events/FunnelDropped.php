<?php

namespace App\Events;

use App\Models\Funnel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FunnelDropped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    const NAME = 'funnel.dropped';

    public Funnel $funnel;

    /**
     * Create a new event instance.
     */
    public function __construct(Funnel $funnel)
    {
        $this->funnel = $funnel;
    }

    /**
     * The name of the queue on which to place the broadcasting job.
     *
     * @var string
     */
    public $queue = 'broadcast';

    /**
     * Get the name of the notification event being broadcast.
     */
    public function broadcastAs()
    {
        return self::NAME;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('funnels.' . $this->funnel->id),
        ];
    }
}
