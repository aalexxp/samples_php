<?php

namespace App\Schedulers;

use App\Events\MilestoneNotChanged;
use App\Models\Property;
use App\Models\Workflow\Trigger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class CheckMilestoneNotChanged extends Command
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:not-changed-milestones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Not Changed milestones';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $triggers = Trigger::where('trigger_id', '=', 'milestone_not_changed')->get();
        foreach ($triggers as $trigger) {
            $date = new \Carbon\Carbon('now');
            $date->subDays($trigger->data['days']);
            $date->subHours($trigger->data['hours']);
            $properties = Property::whereNotNull('milestone_changed_at')
                ->where('milestone_changed_at', '<', $date);
            if ($trigger->data['milestone_id'] > 0) {
                $properties->where('milestone_id', '=', $trigger->data['milestone_id']);
            } else {
                $properties->where('milestone_id', '>', 0);
            }
            $properties->leftJoin('triggers_delayed_log', function ($join) {
                $join->on('properties.id', '=', 'triggers_delayed_log.entity_id')
                    ->where('triggers_delayed_log.entity_name', '=', Property::class);
            })
                ->whereNull('triggers_delayed_log.id');
            $properties->select('properties.*');
            $properties = $properties->get();
            foreach ($properties as $property) {
                if ($property->id) {
                    try {
                        $property = Property::whereId($property->id)->first();
                        event(new MilestoneNotChanged($property));
                        DB::table('triggers_delayed_log')->insert([
                            'entity_id' => $property->id,
                            'entity_name' => Property::class,
                            'trigger_id' => $trigger->id
                        ]);
                    } catch (\Exception $e) {

                    }
                }
            }
        }
    }

}