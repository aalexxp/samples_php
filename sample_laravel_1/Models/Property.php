<?php

namespace App\Models;

use App\Commands\SortableTrait;
use App\Commands\TagsTrait;
use App\Models\Interfaces\IsAddressInterface;
use App\Models\Interfaces\IsTaggableInterface;
use App\Models\Tracks\Track;
use App\Models\Tracks\TrackDefinition;
use App\Services\AddressService;
use App\Services\PhoneService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;


/**
 * App\Models\Property
 *
 * @property int $id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property int|null $creator_id
 * @property int|null $owner_id
 * @property int|null $staff_id
 * @property int|null $milestone_id
 * @property \Carbon\Carbon|null $milestone_changed_at
 * @property int|null $status_id
 * @property string|null $full_address
 * @property string|null $street
 * @property string|null $suburb
 * @property string|null $state
 * @property string|null $postcode
 * @property int|null $value
 * @property string|null $description
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property int|null $remote_id
 * @property int|null $import_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $country
 * @property int $beds
 * @property string|null $private
 * @property int $type_id
 * @property int|null $listing_id
 * @property \Carbon\Carbon|null $listing_updated
 * @property string|null $street_full
 * @property int|null $closest_task_id
 * @property \Carbon\Carbon|null $closed_at
 * @property float|null $commission
 * @property \Carbon\Carbon|null $tasks_imported_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Action[] $actions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Contact[] $agents
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Task[] $autocheckTasks
 * @property-read \App\Models\Task|null $closest_task
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CustomFields[] $custom_fields
 * @property mixed $active_call
 * @property-read mixed $address
 * @property-read mixed $available_tags
 * @property-read \Model|null|object|static $closest_unfinished_task
 * @property-read mixed $owner_main_phone
 * @property-read mixed $update_url
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Image[] $images
 * @property-read \App\Models\Listing $listing
 * @property-read \App\Models\Milestone|null $milestone
 * @property-read \App\Models\Contact|null $owner
 * @property-read \App\Models\User|null $staff
 * @property-read \App\Models\Status|null $status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Task[] $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tracks\Track[] $tracks
 * @property-read \App\Models\PropertyType|null $type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Url[] $urls
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Property onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property sortable()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereBeds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereClosestTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereImportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereListingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereListingUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereMilestoneChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereMilestoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property wherePrivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereRemoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereStaffId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereStreetFull($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereSuburb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereTasksImportedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereValue($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Property withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Property withoutTrashed()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tracks\TrackDefinition[] $track_definitions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tracks\Track[] $attached_tracks
 * @property string|null $call_description
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Property whereCallDescription($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Contact[] $other_contacts
 */
class Property extends Model implements IsAddressInterface, IsTaggableInterface
{
    public const ENTITY_ID = 1;

    public const CAPSULE_AGENT_SELECTED_ID = 66762;

    use SortableTrait, TagsTrait, SoftDeletes, Searchable;

    protected $dates = [
        'created_at',
        'updated_at',
        'milestone_changed_at',
        'deleted_at',
        'listing_updated',
        'closed_at',
        'tasks_imported_at'
    ];

    protected $fillable = [
        'owner_id',
        'staff_id',
        'milestone_id',
        'status_id',
        'street',
        'street_full',
        'suburb',
        'state',
        'postcode',
        'value',
        'description',
        'country',
        'full_address',
        'name',
        'beds',
        'type_id',
        'private',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'type_id' => 'integer',
        'beds' => 'integer',
        'remote_id' => 'integer',
        'import_id' => 'integer',
        'id' => 'integer'
    ];

    protected $is_active_call = false;
    protected $ownerMainPhone = false; // let's add to cache main phone

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $data = [
            "full_address" => null,
            "street" => null,
            "suburb" => null,
            "state" => null,
            "postcode" => null,
            "description" => null,
            "name" => null,
            "country" => null,
            "private" => null
        ];
        return $data;
    }

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function type()
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function track_definitions()
    {
        return $this->hasMany(TrackDefinition::class, 'property_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function listing()
    {
        return $this->hasOne(Listing::class, 'listing_id', 'listing_id');
    }

    public function agents()
    {
        return $this->belongsToMany(Contact::class,
            'contacts_properties')->withTimestamps()->withPivot([
            'priority',
            'appointed_at',
            'referred_at',
            'listed_at',
            'declined_at',
            'agency_id',
            'sold_at',
            'properties_filter'
        ])->orderBy('pivot_priority', 'asc');
    }

    public function other_contacts()
    {
        return $this->belongsToMany(Contact::class, 'contacts_others_properties')->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function autocheckTasks($autocheck = null, $agentNumber = null)
    {
        return $this->hasMany(Task::class)
            ->with(['track_task'])
            ->whereHas('track_task', function ($query) use ($autocheck, $agentNumber) {
                $query->where('autocheck', $autocheck);
                if ($agentNumber) {
                    $query->where(function ($q) use ($agentNumber) {
                        $q->orWhere('autocheck_agent_number', '=', null)
                            ->orWhere('autocheck_agent_number', '=', $agentNumber);
                    });
                }
            });
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'entity_tags', 'entity_id')->wherePivot('entity_type', '=',
            self::ENTITY_ID)->withTimestamps();
    }

    public function staff()
    {
        return $this->belongsTo(User::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }

    public function urls()
    {
        return $this->morphMany(Url::class, 'entity');
    }

    public function owner()
    {
        return $this->belongsTo(Contact::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'entity');
    }

    public function custom_fields()
    {
        return $this->hasMany(CustomFields::class, 'entity_id', 'id')
            ->where('entity_type', '=', CustomTypes::ENTITY_PROPERTY);
    }

    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    public function closest_task()
    {
        return $this->belongsTo(Task::class);
    }

    public function getUpdateUrlAttribute()
    {
        return route('property.update', $this->id);
    }

    public function save(array $options = [])
    {
        if (empty($this->creator_id) AND !empty(\Auth::user())) {
            $this->creator_id = \Auth::user()->id;
        }
        if (empty($this->id) AND
            empty($this->staff_id)) {
            // we should manually add staff
            $this->staff_id = $this->creator_id;
        }

        parent::save($options);
    }

    public function getAddressAttribute()
    {
        $as = new AddressService();

        if (isset($this->attributes['name'])) {
            return $as->beautifulAddress($this, $this->attributes['name']);
        } elseif (isset($this->attributes['full_address'])) {
            return $as->beautifulAddress($this);
        } else {
            return '';
        }
    }

    public function setActiveCallAttribute($is_active_call)
    {
        $this->is_active_call = $is_active_call;
    }

    public function getActiveCallAttribute()
    {
        return $this->is_active_call;
    }

    /** @deprecated */
    public function tracks()
    {
        return $this->belongsToMany(Track::class, 'property_tracks')->withTimestamps();
    }

    public function attached_tracks()
    {
        return $this->hasManyThrough(Track::class, TrackDefinition::class, 'property_id', 'id', 'id', 'track_id');
    }


    /**
     * @deprecated Use closest_task with eager loading instead
     *
     * @return Model|null|object|static
     */
    public function getClosestUnfinishedTaskAttribute()
    {
        return Task::wherePropertyId($this->id)->whereNull('completed_by')->orderBy('due_utc', 'desc')->first();
    }

    public function getOwnerMainPhoneAttribute()
    {
        if ($this->ownerMainPhone === false) {
            $phone = (new PhoneService())->getMainPhone($this->owner_id);
            if ($phone) {
                $this->ownerMainPhone = $phone->phone;
            } else {
                $this->ownerMainPhone = null;
            }
        }

        return $this->ownerMainPhone;
    }

    protected static $fieldTypes = [];

    public function custom_field_by_capsule_id($capsuleId)
    {
        if (!self::$fieldTypes) {
            self::$fieldTypes = CustomTypes::all(['id', 'capsule_id'])->pluck('id', 'capsule_id')->toArray();
        }

        $fieldId = self::$fieldTypes[$capsuleId] ?? null;

        if (!$fieldId) {
            return null;
        }

        $this->loadMissing(['custom_fields']);
        $cf = $this->custom_fields->keyBy('field_id');
        return $cf[$fieldId] ?? null;
    }
}
