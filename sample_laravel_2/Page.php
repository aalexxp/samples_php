<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{

    protected $guarded = ['id'];

    /**
     * @param $data
     *
     * @return Page|Model|null|static
     */
    public static function getUpdatePage($data) {
        $page = self::where('id', $data['idOrSlug'])
            ->orWhere('slug', $data['idOrSlug'])
            ->first();
        if (!$page) {
            $page = self::createPage($data);
        } else {
            $page->fillGeneralFields($data, false);
            $page->save();
        }

        return $page;
    }

    /**
     * Create new Page
     *
     * @param $data
     *
     * @return Page
     */
    public static function createPage($data) {
        $page = new Page();
        $page->fillGeneralFields($data);
        $page->save();

        return $page;
    }

    public function fillGeneralFields($data, $newSlug = true) {
        $this->title = $data['title'];
        $this->conference_id = $data['conference_id'];
        $this->type = $data['type'];
        $this->published = $data['published'];
        $this->parent_id = $data['parent_id'];
        $slug = $data['slug'] ?: Str::slug($data['title']);
        if ($newSlug) {
            $this->slug = self::generateSlug($slug, $data['conference_id']);
        } else {
            $this->slug = self::generateSlug($slug, $data['conference_id'], $this->id);
        }
    }

    public static function generateSlug($slug, $conferenceId, $except = false, $index = 0) {
        $testSlug = $index === 0 ? $slug : $slug . $index;
        $query = self::where('conference_id', $conferenceId)->where('slug', $testSlug);
        if ($except) {
            $query->where('id', '<>', $except);
        }
        if ($query->first()) {
            return self::generateSlug($slug, $conferenceId, $except, ++$index);
        } else {
            return $testSlug;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent() {
        return $this->belongsTo('App\Page', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children() {
        return $this->hasMany('App\Page', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conference() {
        return $this->belongsTo('App\Conference');
    }

    /**
     * Store page content
     *
     * @param $data
     *
     * @return mixed
     */
    public function storeContent($data) {
        switch ($data['page_type']) {
            case 'content_page':
                return $this->storeContentPage($data);
                break;
            case 'tabs_page':
                return $this->storeTabsPage($data);
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Not supported page type.'
                ]);
        }
    }

    /**
     * Store page content for content_type page
     *
     * @param $data array
     */
    protected function storeContentPage($data) {
        $tabs = $this->tabs;
        if (count($tabs) === 0) {
            $tab = new PageTab();
            $this->tabs()->save($tab);
        } else {
            $tab = $tabs->first();
        }
        $tab->storeContent($data['page_data']);
        $this->type = '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tabs() {
        return $this->hasMany('App\PageTab');
    }

    /**
     * Store page content for tabs_type page
     *
     * @param $data
     */
    protected function storeTabsPage($data) {
        $tabs_id = array_map(function ($item) {
            return isset($item['id']) ? $item['id'] : false;
        }, $data['page_data']);
        $tabsToRemove = $this->tabs->whereNotIn('id', $tabs_id);
        if (count($tabsToRemove)) {
            $tabsToRemove->each(function ($tab) {
                $tab->delete();
            });
        }
        foreach ($data['page_data'] as $tabData) {
            if (isset($tabData['id']) && $tabData['id'] && $this->tabs->find($tabData['id'])) {
                $tab = $this->tabs->find($tabData['id']);
                $tab->title = $tabData['title'] ?? '';
                $tab->index = $tabData['index'] ?? 0;
                $tab->quick_link_enabled = $tabData['quick_link_enabled'] ?? false;
                $tab->quick_link_label = $tabData['quick_link_label'] ?? '';
                $tab->save();
            } else {
                $tab = new PageTab();
                $tab->title = $tabData['title'] ?? '';
                $tab->index = $tabData['index'] ?? 0;
                $tab->quick_link_enabled = $tabData['quick_link_enabled'] ?? false;
                $tab->quick_link_label = $tabData['quick_link_label'] ?? '';
                $this->tabs()->save($tab);
            }
            $tab->storeContent($tabData['data']);
        }
    }
}
