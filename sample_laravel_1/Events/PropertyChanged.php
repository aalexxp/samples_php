<?php
/**
 * Created by PhpStorm.
 * User: andrej
 * Date: 01.11.2017
 * Time: 19:13
 */

namespace App\Events;

use App\Models\Listing;
use App\Models\Property;
use App\Services\Spa\PropertyLoaderService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PropertyChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $property;
    protected $changes = [];
    protected const DELTA_DELAY_IN_SECONDS = 2;

    public $type;
    public $notify_delay = 0;
    public $property_id;
    public $milestone;


    public function __construct(Property $property, $type, $changes = [])
    {
        $this->property = $property;
        $this->type = $type;
        $this->changes = $changes;
        $this->property_id = $property->id;

        $property->load(['milestone']);
        if ($property->milestone) {
            $this->milestone = $property->milestone->name;
        }

        $diff = $property->created_at->diffInSeconds();
        if ($diff < PropertyLoaderService::DELAY_BEFORE_SHOWING_PROPERTY_IN_SECONDS) {
            $this->notify_delay = PropertyLoaderService::DELAY_BEFORE_SHOWING_PROPERTY_IN_SECONDS + self::DELTA_DELAY_IN_SECONDS;
        }
    }

    public function getChanges()
    {
        return $this->changes;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getListing()
    {
        return Listing::whereListingId($this->property->listing_id)->orderBy('created_at', 'desc')->first();
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return ['property_changed'];
    }
}