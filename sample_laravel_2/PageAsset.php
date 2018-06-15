<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PageAsset extends Model
{

    /**
     * @param $page string
     * @param $conferenceId int|string
     * @param $data array
     * @param $type string
     *
     * @return mixed
     */
    public static function addAssets($page, $conferenceId, $data, $type) {
        $items = [];
        $now = Carbon::now();
        logger('PageAsset::addAssets --- ', [$data]);
        foreach ($data as $asset) {
            try {
                $id = $asset;
                $description = '';
                $preview = 0;
                if (is_array($asset) && isset($asset['id'])) {
                    $id = $asset['id'];
                    $description = isset($asset['description']) ? $asset['description'] : '';
                    if (isset($asset['preview'])) {
                        $preview = $asset['preview'];
                    }
                }
                if (!$id) continue;
                $items[] = [
                    'conference_id' => $conferenceId,
                    'page'          => $page,
                    'type'          => $type,
                    'asset'         => $id,
                    'description'   => $description,
                    'preview'       => $preview,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            } catch (\Exception $e) {
                logger('PageAsset::addAssets --- ', [$e->getMessage(), $asset]);
            }
        }
        static::where('conference_id', $conferenceId)->where('page', $page)->where('type', $type)->delete();
        return \DB::table((new self())->getTable())->insert($items);
    }

    public function asset() {
        return $this->belongsTo('App\ConferenceAsset', 'asset', 'id');
    }

    public function gallery() {
        return $this->belongsTo('App\Gallery', 'asset', 'id');
    }

    public function preview() {
        return $this->belongsTo('App\ConferenceAsset', 'preview', 'id');
    }

}
