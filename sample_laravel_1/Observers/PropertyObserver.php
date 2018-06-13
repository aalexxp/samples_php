<?php

namespace App\Observers;

use App\Events\MilestoneChanged;
use App\Events\PropertyChanged;
use App\Models\Milestone;
use App\Models\MilestoneHistory;
use App\Models\Property;
use App\Models\Status;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PropertyObserver extends AbstractObserver
{

    // This event must be emitted after saving a model
    protected static $milestoneChangedEvent = [];
    protected static $changes = [];

    protected function updatingFunction(Property $model, array $changes)
    {
        // Here is the best place for tracking changes of milestone
        if (get_class($model) === Property::class) {
            if (isset($changes['milestone_id'])) {
                $oldMilestone = $changes['milestone_id']['old'] ? Milestone::where('id',
                    $changes['milestone_id']['old'])->first() : null;
                $newMilestone = $changes['milestone_id']['new'] ? Milestone::where('id',
                    $changes['milestone_id']['new'])->first() : null;

                self::$milestoneChangedEvent[$model->id] = ['new' => $newMilestone, 'old' => $oldMilestone];
                $model->milestone_changed_at = new Carbon('now');
                $milestone = Milestone::whereId($changes['milestone_id']['new'])->first();
                if (!empty($milestone->id) && $milestone->status_id > 0 && $model->status_id !== $milestone->status_id) {
                    $model->status_id = $milestone->status_id;
                    Task::where('property_id', '=', $model->id)->update(['property_status_id' => $milestone->status_id]);
                }
            }

            self::$changes[$model->id] = $changes;
        }

    }

    public function updated(Property $property)
    {
        if (!empty(self::$milestoneChangedEvent[$property->id])) {
            MilestoneHistory::create([
                'property_id' => $property->id,
                'milestone_from_id' => optional(self::$milestoneChangedEvent[$property->id]['old'])->id,
                'milestone_to_id' => optional(self::$milestoneChangedEvent[$property->id]['new'])->id
            ]);
            event(new MilestoneChanged(
                    $property,
                    self::$milestoneChangedEvent[$property->id]['new'],
                    self::$milestoneChangedEvent[$property->id]['old'])
            );
            DB::table('triggers_delayed_log')->where('entity_name', '=', Property::class)->where('entity_id', '=', $property->id)->delete();
        }
        event(new PropertyChanged($property, 'changed',
            isset(self::$changes[$property->id]) ? self::$changes[$property->id] : null));
    }

}