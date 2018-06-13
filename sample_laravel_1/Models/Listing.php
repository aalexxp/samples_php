<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Listing
 *
 * @property int $id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property int $listing_id
 * @property string|null $rea_listing_id
 * @property string|null $title
 * @property string|null $channel
 * @property string|null $status
 * @property int|null $agent_id
 * @property int|null $agency_id
 * @property string|null $date_sold
 * @property string|null $modified_date
 * @property string|null $address_suburb
 * @property string|null $address_street
 * @property string|null $address_state
 * @property string|null $address_postcode
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int|null $price
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereAddressPostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereAddressState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereAddressStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereAddressSuburb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereDateSold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereListingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereModifiedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereReaListingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Listing whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Company $agency
 * @property-read \App\Models\Contact $agent
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Listing onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Listing withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Listing withoutTrashed()
 * @property-read \App\Models\Property $property
 */
class Listing extends Model
{
    use SoftDeletes;

    const STATUS_SOLD = 'sold';
    const STATUS_LISTED = 'listed';
    const ENTITY_ID = 11;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'date_sold',
        'modified_date'
    ];

    protected $casts = [
        'agent_id' => 'integer',
        'agency_id' => 'integer',
        'price' => 'integer'
    ];


    protected $fillable = [
        'created_at',
        'updated_at',
        'listing_id',
        'rea_listing_id',
        'title',
        'channel',
        'status',
        'agent_id',
        'agency_id',
        'date_sold',
        'modified_date',
        'address_suburb',
        'address_street',
        'address_state',
        'address_postcode',
        'latitude',
        'longitude',
        'price',
    ];

    public function setPriceAttribute($price)
    {
        if ('' === $price) {
            $this->attributes['price'] = null;
        }
    }

    public function agent()
    {
        return $this->belongsTo(Contact::class, 'ronas_id');
    }

    public function agency()
    {
        return $this->belongsTo(Company::class, 'ronas_id');
    }

    public function property()
    {
        return $this->hasOne(Property::class, 'listing_id', 'listing_id');
    }
}
