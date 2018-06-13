<?php

namespace App\Listeners\AgentAcceptedConditions;

use App\Events\AgentAcceptedConditions;
use App\Models\Tracks\TrackTask;
use App\Models\Task;
use App\Services\TemplateTagsService;

class AutoCheck
{

    protected $tts = null;

    public function __construct(TemplateTagsService $tts)
    {
        $this->tts = $tts;
    }

    public function handle(AgentAcceptedConditions $event)
    {
        $property = $event->getProperty();
        $agent = $event->getAgent();

        $agentNumber = $this->tts->getAgentNumber($agent, $property);

        $autocheck = TrackTask::AGENT_ACCEPTED_TERMS;

        $tasks = $property->autocheckTasks($autocheck, $agentNumber)->get();

        /** @var Task $task */
        foreach ($tasks as $task) {
            if ($task->status == Task::STATUS_OPEN) {
                $task->status = Task::STATUS_COMPLETED;
                $task->save();
            }
        }

        return true;
    }
}