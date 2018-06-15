<?php

namespace App;

use App\Helpers\General;
use App\Mail\DelegateResetPasswordNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Schema;

/**
 * @mixin \Eloquent
 */
class Delegate extends Authenticatable
{

    use Notifiable;

    public $table = "delegates";
    public $fillable = ['email', 'title', 'first_name', 'last_name', 'country', 'job_title', 'organisation', 'photo', 'confirmed', 'enabled', 'uic', 'rank', 'group', 'hidden'];
    public $guarded = ['id', 'conference_id', 'email', 'password', 'uic'];
    public $hidden = ['password'];

    public static function boot() {
        parent::boot();

        // cause a delete of a product to cascade to children so they are also deleted
        static::deleting(function ($delegate) {
            $delegate->personalAppointments()->delete();
            $delegate->unavailableTimeslots()->delete();
            $delegate->meetingRequests()->delete();
        });
    }

    public function conference() {
        return $this->belongsTo('App\Conference');
    }

    public function photo() {
        return $this->hasOne(ConferenceAsset::class, 'id', 'photo');
    }

    public function profile() {
        return $this->hasMany(DelegateMeta::class);
    }

    public function getProfileField($label) {
        return $this->profile->where('label', $label)->first()->value;
    }

    public function getProfileFields() {
        $profile = [];

        foreach ($this->profile as $p) {
            $field = RegformField::find($p->regform_field_id);
            if ($field) {
                $profile[$field->name] = $p->value;
            }
        }

        $this->meta = [];
        $this->meta = $profile;
    }

    public function getProfileCSVFields() {
        $profile = [];


        foreach ($this->profile as $p) {
            $field = RegformField::find($p->regform_field_id);
            if ($field) {
                $profile[$field->CSVLabel] = $p->value;
            }
        }

        $this->meta = [];
        $this->meta = $profile;
    }

    public function getCsvColumns() {

        $delegate = new Delegate();

        $delegateTable = $delegate->getTable();

        $forbiddenValues = ['id', 'conference_id', 'remember_token', 'password', 'created_at', 'updated_at', 'photo'];

        $columns = Schema::getColumnListing($delegateTable);

        $regform_meta = Auth::user()->conference->regformFields->where('default', '!=', 1)->whereNotIn('type', ['start', 'end', 'header', 'subheader'])->pluck('CSVLabel')->toArray();

        $columns = array_merge($columns, $regform_meta);

        foreach ($columns as $k => $v) {
            if (in_array($v, $forbiddenValues)) {
                unset($columns[$k]);
            }
        }

        return array_values($columns);
    }

    public function getFillableColumnsAdmin() {

        $delegate = new Delegate();

        $delegateTable = $delegate->getTable();

        $forbiddenValues = ['id', 'conference_id', 'remember_token', 'created_at', 'updated_at', 'photo'];

        $columns = Schema::getColumnListing($delegateTable);

        $regform_meta = Auth::user()->conference->regformFields->where('default', '!=', 1)->whereNotIn('type', ['start', 'end', 'header', 'subheader'])->pluck('name')->toArray();

        $columns = array_merge($columns, $regform_meta);

        foreach ($columns as $k => $v) {
            if (in_array($v, $forbiddenValues)) {
                unset($columns[$k]);
            }
        }

        return array_values($columns);
    }

    public function getFillableColumns() {

        $delegate = new Delegate();

        $delegateTable = $delegate->getTable();

        $forbiddenValues = ['id', 'conference_id', 'remember_token', 'created_at', 'updated_at', 'photo'];

        $columns = Schema::getColumnListing($delegateTable);

        $regform_meta = Conference::where('url_slug', General::getConference())->first()->regformFields->where('default', '!=', 1)->whereNotIn('type', ['start', 'end', 'header', 'subheader'])->pluck('name')->toArray();

        $columns = array_merge($columns, $regform_meta);

        foreach ($columns as $k => $v) {
            if (in_array($v, $forbiddenValues)) {
                unset($columns[$k]);
            }
        }

        return array_values($columns);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string $token
     * @return void
     */
    public function sendPasswordResetNotification($token) {
        $this->notify(new DelegateResetPasswordNotification($token));
    }

    /**
     * Get the meta for the delegate.
     */
    public function meetingRequests() {
        return $this->hasMany(MeetingRequest::class);
    }

    public function unavailableTimeslots() {
        return $this->hasMany(UnavailableTimeslot::class);
    }

    public function rankedCorporates() {
        return $this->hasMany(CorporateRanking::class);
    }

    public function personalAppointments() {
        return $this->hasMany(PersonalAppointment::class);
    }

    public function corporate() {
        return $this->belongsToMany(Corporate::class, 'corporate_delegates');
    }

    public function scopeRankedCorporate(Builder $query, $corporate_id, $timeslot_id) {
        return $query->whereHas('rankedCorporates', function ($q) use ($corporate_id) {
            $q->where('corporate_id', $corporate_id)->orderBy('rank');
        });
    }

}
