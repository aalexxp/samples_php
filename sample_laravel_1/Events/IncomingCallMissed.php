<?php

namespace App\Events;

use App\Models\Messages\Message;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class IncomingCallMissed implements ShouldBroadcast
{

    use SerializesModels;

    protected $users = [];

    public function __construct($userIds)
    {
        foreach ($userIds as $userId) {
            $this->users[$userId] = $userId;
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {

        $result = [];
        foreach ($this->users AS $userId) {
            $result[] = 'messages.' . $userId;
        }

        if (!$result) {
            $result = ['messages'];
        }

        \Log::debug(print_r($result, 1));
        return $result;

    }

    public function broadcastAs()
    {
        return 'incoming-message';
    }

}