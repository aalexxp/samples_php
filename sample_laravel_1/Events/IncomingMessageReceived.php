<?php

namespace App\Events;

use App\Models\Messages\Message;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class IncomingMessageReceived implements ShouldBroadcast
{

    use SerializesModels;

    /** @var Message|null */
    protected $message;

    public $messageId;
    public $messageType;
    public $messageUrl = '';
    protected $users = [];

    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->messageId = $message->id;
        $this->messageType = class_basename($message);

        // We should send the message to staff which is connected to the property
        if ($message->property_id) {
            $message->load(['property']);
            if ($message->creator_id) {
                $this->users[$message->creator_id] = $message->creator_id;
            } elseif ($message->property->staff_id) {
                $this->users[$message->property->staff_id] = $message->property->staff_id;
            }
            $this->messageUrl = route('property.show', ['id' => $message->property->id, '_' => mt_rand()]) . '#' . $message->action()->first()->activity_id;
        } elseif ($message->contact_id) {
            $message->load(['contact']);
            if ($message->contact->id) {
                $this->messageUrl = route('contact.show', ['id' => $message->contact->id, '_' => mt_rand()]) . '#' . $message->action()->first()->activity_id;
            }
        }

        // Let's add users to UserList
        foreach (\func_get_args() AS $arg) {
            if (is_numeric($arg)) {
                $arg = (int)$arg;
                $this->users[$arg] = $arg;
            } elseif (is_a($arg, User::class)) {
                $this->users[$arg->id] = $arg->id;
            }
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
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

    public function broadcastAs(): string
    {
        return 'incoming-message';
    }

}