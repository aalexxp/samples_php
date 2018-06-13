<?php

namespace App\Listeners;

use App\Models\Agent\PropertyData;


class AgentPropertyDataListener
{

    private function SavePropertyData($event, $type)
    {
        $data = new PropertyData();
        $data->property_id = $event->getProperty()->id;
        $data->contact_id = $event->getAgent()->id;
        $data->type = $type;
        $data->save();
    }

    public function onAgentPropertyAction($event)
    {
        if ($event->getType() != null) {
            $this->SavePropertyData($event, $event->getType());
        }
    }

    public function onAgentInvited($event)
    {
        $this->SavePropertyData($event, PropertyData::TYPE_REFERRAL);
    }

    public function onAcceptedConditions($event)
    {
        $this->SavePropertyData($event, PropertyData::TYPE_ACCEPT);
    }

    public function onDisagreedConditions($event)
    {
        $this->SavePropertyData($event, PropertyData::TYPE_DECLINE);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\AgentAcceptedConditions',
            'App\Listeners\AgentPropertyDataListener@onAcceptedConditions'
        );

        $events->listen(
            'App\Events\AgentDisagreedConditions',
            'App\Listeners\AgentPropertyDataListener@onDisagreedConditions'
        );

        $events->listen(
            'App\Events\AgentInvited',
            'App\Listeners\AgentPropertyDataListener@onAgentInvited'
        );
        $events->listen(
            'App\Events\AgentPropertyAction',
            'App\Listeners\AgentPropertyDataListener@onAgentPropertyAction'
        );
    }
}