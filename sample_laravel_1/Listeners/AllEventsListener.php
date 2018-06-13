<?php

namespace App\Listeners;

use App\Services\TriggerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class AllEventsListener implements ShouldQueue
{

    protected $_triggerService = null;


    protected static $informedList = [];

    public function __construct(TriggerService $triggerService)
    {
        $this->_triggerService = $triggerService;
    }

    public function onWildcardApplicationEvent($name = null, $data = [])
    {

        // let's log all events except PropertyChanged (because there are a lot of events of this type)
        if (empty(self::$informedList[$name]) AND $name !== 'App\Events\PropertyChanged') {
            \Log::debug('Wildcard Event was executed, NAME: ' . $name);
            self::$informedList[$name] = $name;
        }

        try {
            $this->_triggerService->applyEvent($name, $data);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\*',
            [$this, 'onWildcardApplicationEvent']
        );
    }

}