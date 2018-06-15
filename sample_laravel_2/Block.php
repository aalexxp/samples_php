<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $guarded = ['id'];

    public function storeContent($data) {
        foreach ($data as $name => $content) {
            $item = $this->data()->where('name', $name)->first();
            if (is_array($content)) {
                $content = \GuzzleHttp\json_encode($content);
            }
            if (!$item) {
                $item = new BlockData();
                $item->name = $name;
                $item->content = $content;
                $this->data()->save($item);
            } else {
                $item->content = $content;
                $item->save();
            }
        }
    }

    public function data() {
        return $this->hasMany('App\BlockData');
    }
}
