<?php

namespace App\Events;

use App\Models\Comms\Template;
use App\Models\Property;
use Illuminate\Queue\SerializesModels;

class EmailSent
{
    protected $template;
    protected $property;

    use SerializesModels;

    public function __construct(Template $template, Property $property)
    {
        $this->property = $property;
        $this->template = $template;
    }


    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return Property|null
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

}