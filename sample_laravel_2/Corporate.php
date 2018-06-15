<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Corporate extends Model
{
    protected $table = 'corporates';
    protected $fillable = ['title', 'country', 'name', 'industry', 'bio'];
    protected $hidden = ['briefing_notes'];

    public function delegate() {
        return $this->belongsToMany(Delegate::class, 'corporate_delegates');
    }

    public function meetingTimeslots() {
        return $this->belongsToMany(MeetingTimeslot::class, 'meetingtimeslots_corporates', 'corporate_id', 'meeting_timeslot_id');
    }
}