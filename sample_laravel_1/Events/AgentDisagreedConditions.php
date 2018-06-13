<?php

namespace App\Events;

use App\Models\Contact;
use App\Models\Property;
use Illuminate\Queue\SerializesModels;

class AgentDisagreedConditions
{

    protected $property;
    protected $agent;

    use SerializesModels;

    public function __construct(Property $property, Contact $agent)
    {
        $this->property = $property;
        $this->agent    = $agent;
    }


    public function getProperty(): Property
    {
        return $this->property;
    }

    public function getAgent(): Contact
    {
        return $this->agent;
    }

    public function getContact(): Contact
    {
        return $this->agent;
    }
}