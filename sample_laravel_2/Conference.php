<?php namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 */
class Conference extends Model
{
    public function assets() {
        return $this->hasMany('App\ConferenceAsset')->orderBy('id', 'DESC');
    }

    public function getRouteKeyName() {
        return 'url_slug';
    }

    public function content() {
        return $this->hasMany('App\Content');
    }

    public function speakers() {
        return $this->hasMany('App\Speaker');
    }

    public function hotels() {
        return $this->belongsToMany('App\Hotel', 'hotel_conference')->withPivot('distance');
    }

    public function days() {
        return $this->hasMany('App\Day');
    }

    public function delegates() {
        return $this->hasMany(Delegate::class);
    }

    public function pages() {
        return $this->hasMany(Page::class);
    }

    public function regformFields() {
        return $this->hasMany(RegformField::class);
    }
}
