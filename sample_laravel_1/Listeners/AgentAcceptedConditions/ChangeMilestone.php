<?php

namespace App\Listeners\AgentAcceptedConditions;

use App\Events\AgentAcceptedConditions;
use App\Services\ParametersService;
use App\Services\TemplateTagsService;

class ChangeMilestone
{

    protected $parameters = null;
    protected $tts = null;

    public function __construct(ParametersService $parameters, TemplateTagsService $tts)
    {
        $this->parameters = $parameters;
        $this->tts = $tts;
    }

    public function handle(AgentAcceptedConditions $event)
    {
        if ($milestone = $this->parameters->get(ParametersService::PARAM_CHANGE_MILESTONE_AFTER_ACCEPT)) {
            $property = $event->getProperty();
            if ($property->milestone_id !== $milestone) {
                $property->milestone_id = $milestone;
                $property->save();
            }
        }
    }
}