<?php

namespace App\Http\Controllers\Frontend;

use App\Conference;
use App\Content;
use App\Speaker;
use DB;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class SpeakerController extends BaseController
{

    /**
     * Show Speakers page
     *
     * @param Conference $conference
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Conference $conference) {
        $content = Content::GetContent('speakers', $conference);
        $confirmed_speakers = isset($content['confirmed_speakers']) ? \GuzzleHttp\json_decode($content['confirmed_speakers']) : [];
        $previous_speakers = isset($content['previous_speakers']) ? \GuzzleHttp\json_decode($content['previous_speakers']) : [];
        $featured_speakers = isset($content['featured_speakers']) ? \GuzzleHttp\json_decode($content['featured_speakers']) : [];

        if (!empty($featured_speakers)) {
            $ids_ordered = implode(',', $featured_speakers);
            $featured = $conference->speakers()->whereIn('id', $featured_speakers)
                ->where('enabled', true)
                ->orderByRaw(DB::raw("FIELD(id, $ids_ordered)"))
                ->get();
        } else {
            $featured = $conference->speakers()->where('type', '1')->where('enabled', true)->get();
        }

        if (!empty($confirmed_speakers)) {
            $ids_ordered = implode(',', $confirmed_speakers);
            $confirmed = $conference->speakers()->whereIn('id', $confirmed_speakers)
                ->where('enabled', true)
                ->orderByRaw(DB::raw("FIELD(id, $ids_ordered)"))
                ->get();
        } else {
            $confirmed = $conference->speakers()->where('type', '1')->where('enabled', true)->get();
        }

        if (!empty($previous_speakers)) {
            $ids_ordered = implode(',', $previous_speakers);
            $prev = $conference->speakers()->whereIn('id', $previous_speakers)
                ->where('enabled', true)
                ->orderByRaw(DB::raw("FIELD(id, $ids_ordered)"))
                ->get();
        } else {
            $prev = $conference->speakers()->where('type', '2')->where('enabled', true)->get();
        }


        return $this->getView(
            'speakers',
            [
                'global'    => Content::GetContent('global', $conference),
                'speakers'  => $content,
                'confirmed' => $confirmed,
                'previous'  => $prev,
                'featured'  => $featured
            ],
            $conference
        );
    }

    /**
     * Show speaker profile page
     *
     * @param Conference $conference
     * @param Speaker $speaker
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function profile(Conference $conference, Speaker $speaker) {
        $content = Content::GetContent('speakers', $conference);
        $confirmed_speakers = isset($content['confirmed_speakers']) ? \GuzzleHttp\json_decode($content['confirmed_speakers']) : [];
        $previous_speakers = isset($content['previous_speakers']) ? \GuzzleHttp\json_decode($content['previous_speakers']) : [];

        $hidden = false;

        if (!in_array($speaker->id, $confirmed_speakers) && !in_array($speaker->id, $previous_speakers)) {
            $hidden = true;
        }

        return $this->getView(
            'speaker-profile',
            [
                'global'      => Content::GetContent('global', $conference),
                'speakers'    => Content::GetContent('speakers', $conference),
                'speakerList' => $conference->speakers->where('id', '!=', $speaker->id)->where('type', '!=', 3)->groupBy('type'),
                'profile'     => $speaker->toArray(),
                'hidden'      => $hidden
            ],
            $conference
        );
    }
}
