<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PageTab extends Model
{
    protected $guarded = ['id'];

    public function storeContent($data) {
        $blocks_id = array_map(function ($item) {
            return isset($item['id']) ? $item['id'] : false;
        }, $data);
        $blocksToRemove = $this->blocks->whereNotIn('id', $blocks_id);
        if (count($blocksToRemove)) {
            $blocksToRemove->each(function ($block) {
                $block->delete();
            });
        }
        foreach ($data as $blockData) {
            $block = false;
            if (isset($blockData['id'])) {
                $block = $this->blocks()->where('id', $blockData['id'])->first();
                if (isset($blockData['index'])) {
                    $block->index = $blockData['index'];
                    $block->save();
                }
            }
            if (!$block) {
                $block = new Block();
                $block->type = $blockData['block_type'];
                $block->index = isset($blockData['index']) ? $blockData['index'] : 0;
                $this->blocks()->save($block);
            }
            $block->storeContent($blockData['block_data']);
        }
        return true;
    }

    public function blocks() {
        return $this->hasMany('App\Block', 'tab_id');
    }
}
