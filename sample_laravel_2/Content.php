<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Eloquent
 */
class Content extends Model
{

    public static function NavigationItems(Conference $conference) {
        $speakers = self::GetContent('speakers', $conference)->toArray();

        $items = [
            $speakers['menu_order']['content'] => [
                'title' => $speakers['menu_title']['content'],
                'url'   => 'speakers',
            ],
        ];

        ksort($items);

        return $items;
    }

    public static function GetContent($page, Conference $conference) {
        $content = $conference->content()->where('page', $page)->get(['identifier', 'content'])->keyBy('identifier')->toArray();
        $c = [];
        foreach ($content as $k => $v) {
            $c[$k] = $v['content'];
        }

        return $c;
    }

    public static function AssetPath($id) {
        if (is_object($id)) {
            $id = $id->id;
        }
        $asset = ConferenceAsset::find($id);

        if (!$asset) {
            return '';
        }

        return $asset->path;
    }

    public static function GetSingleContent($conference_id, $page, $id) {

        $c = Content::where('conference_id', $conference_id)->where('identifier', $id)->where('page', $page)->first();

        return $c->content;
    }

    public static function GetContentVue($page, Conference $conference) {
        return $conference->content()->where('page', $page)->get(['identifier', 'content AS value'])->keyBy('identifier');
    }


    public static function GetContentCollection($page) {
        return Content::where('page', $page)->where('conference_id', Auth::user()->conference->id)->get()->keyBy('identifier');
    }

    public function conference() {
        return $this->belongsTo('App\Conference');
    }

}
