<?php

namespace App\Models;

use App\Models\CallCenter\Disposition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * App\Models\Milestone
 *
 * @property int $id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property string $name
 * @property int $order
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $probability
 * @property string|null $description
 * @property int $status_id
 * @property int|null $remote_id
 * @property int|null $import_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CallCenter\Disposition[] $dispositions
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Milestone onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereImportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereProbability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereRemoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Milestone withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Milestone withoutTrashed()
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereStatusId($value)
 * @property int $position
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone wherePosition($value)
 * @property string|null $label
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Milestone whereLabel($value)
 */
class Milestone extends Model
{
    use SoftDeletes;

    const SPECIAL_MILESTONE_DUPLICATE = 40;
    public const MILESTONE_CHECK_DUPLICATE_LABEL = 'check_duplicate';
    public const MILESTONE_LEAD_LABEL = 'lead';

    protected $fillable = [
        'name',
        'description',
        'probability',
        'status_id',
        'label'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];


    public function dispositions()
    {
        return $this->belongsToMany(Disposition::class, 'milestones_dispositions')->orderBy('position');
    }

}
