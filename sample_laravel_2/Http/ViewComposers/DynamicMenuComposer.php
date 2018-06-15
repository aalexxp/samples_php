<?php

namespace App\Http\ViewComposers;

use App\Conference;
use App\Content;
use App\Helpers\General;
use App\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DynamicMenuComposer
{

    protected $pages;
    protected $childPages;
    protected $conference;

    /**
     * Create a new profile composer.
     *
     * @param Request $request
     *
     * @return void
     */
    public function __construct(Request $request) {
        $currentConference = $request->segment(1);
        $this->conference = Conference::where('url_slug', $currentConference)->first();

        if (!$this->conference) {
            return;
        }

        $this->renderPages();
    }

    /**
     * Render the pages array
     */
    protected function renderPages() {
        $basePages = $this->getBasePages();
        $this->fillPages($basePages);

        $customPages = $this->getCustomPages();
        $this->fillPages($customPages);

        $this->reorderPages();
    }

    /**
     * @return array
     */
    protected function getBasePages() {
        $pages = [
            [
                'slug'  => 'media-registration',
                'title' => 'Media Registration',
                'route' => General::conferenceRoute('frontend.media.register')
            ],
        ];

        $enableHotelFinder = $this->conference->content()
            ->select('content')
            ->where('page', 'hotel-finder')
            ->where('identifier', 'hotel_finder_enabled')->first();

        if ($enableHotelFinder && $enableHotelFinder->content) {
            $pages[] = [
                'slug'  => 'hotel-finder',
                'title' => 'Hotel Finder',
                'route' => General::conferenceRoute('frontend.hotel-finder')
            ];
        }

        return $pages;
    }

    /**
     * Fill pages array from given content/pages data
     *
     * @param $pages
     */
    protected function fillPages($pages) {
        foreach ($pages as $page) {
            $pageData = $this->getPageData($page);
            if ($pageData['parent_id']) {
                $this->addChildPage($pageData);
            } else {
                $this->addPage($pageData);
            }
        }
    }

    protected function getPageData($page) {
        $content = Content::GetContent($page['slug'], $this->conference);

        if (isset($page['menu_title']) && $page['menu_title']) {
            $content['menu_title'] = $page['menu_title'];
        }

        $has_custom_label = false;
        if (isset($content['menu_title'])) {
            $title = $content['menu_title'];
            $has_custom_label = true;
        } elseif (isset($content['meta_title'])) {
            $title = $content['meta_title'];
        } else {
            $title = $page['title'];
        }
        $child = isset($page['child']) ? $this->renderChildList($page['child']) : [];
        if (isset($page['rendered_child'])) {
            $child = array_merge($child, $page['rendered_child']);
        }
        $pageData = [
            'title'            => $title,
            'has_custom_label' => $has_custom_label,
            'navigation_order' => isset($content['navigation_order']) ? $content['navigation_order'] : null,
            'url'              => $page['route'],
            'child'            => $child,
            'anchor_tags'      => isset($page['anchor_tags']) ? $page['anchor_tags'] : [],
            'parent_id'        => $content['parent_id'] ?? false,
        ];

        return $pageData;
    }

    protected function renderChildList($collection) {
        $list = [];
        foreach ($collection as $subPage) {
            $list[] = $this->getPageData($subPage);
        }

        return $list;
    }

    /**
     * Add child page to pages array
     *
     * @param $page
     * @param null $position
     */
    protected function addChildPage($page, $position = null) {
        if ($position) {
            if (!is_int($position)) {
                $position = intval($position);
            }
            array_splice($this->childPages, $position, 0, $page);
        } else {
            $this->childPages[$page['parent_id']][] = $page;
        }
    }

    /**
     * Add page to pages array
     *
     * @param $page
     * @param null $position
     */
    protected function addPage($page, $position = null) {
        if ($position) {
            if (!is_int($position)) {
                $position = intval($position);
            }
            array_splice($this->pages, $position, 0, $page);
        } else {
            $this->pages[] = $page;
        }
    }

    /**
     * retrieve the list of custom pages
     *
     * @return array
     */
    protected function getCustomPages() {
        $allPages = Page::with(['tabs', 'tabs.blocks', 'tabs.blocks.data'])
            ->where('conference_id', $this->conference->id)
            ->whereNull('parent_id')
            ->where('published', true)->get();

        return $this->renderCustomPagesData($allPages);
    }

    protected function renderCustomPagesData($collection) {
        $data = [];
        foreach ($collection as $page) {

            $metaToArr = Content::GetContentVue($page->slug, $this->conference)->toArray();

            $item = [
                'slug'  => $page->slug,
                'title' => $page->title,
                'route' => General::conferenceRoute('frontend.custom-page', $page->slug)
            ];

            if (isset($metaToArr['navigation_label']) && isset($metaToArr['navigation_label']['value']) && $metaToArr['navigation_label']['value']) {
                $item['menu_title'] = $metaToArr['navigation_label']['value'];
            }

            if (count($page->children)) {
                $item['child'] = $this->renderCustomPagesData($page->children);
            }

            if (isset($this->childPages[$page->id])) {
                $item['rendered_child'] = $this->childPages[$page->id];
            }

            if ($this->doesTabHasAnchors($page)) {
                $item['anchor_tags'] = $this->getTabAnchorLinks($page);
            } else if ($this->doesPageHaveAnchors($page)) {
                $item['anchor_tags'] = $this->getAnchorLinks($page);
            }

            $data[] = $item;
        }

        return $data;
    }

    protected function doesTabHasAnchors($page) {
        $doesTabHasAnchor = false;

        foreach ($page->tabs as $tab) {
            if ($tab->quick_link_enabled) {
                $doesTabHasAnchor = true;
            }
        }

        return $doesTabHasAnchor;
    }

    protected function getTabAnchorLinks($page) {
        $tab_anchor_links = [];

        $orderedTabs = General::orderDataBy($page->tabs->toArray(), 'index');


        foreach ($orderedTabs as $tab) {
            if ($tab['quick_link_enabled'] && $tab['quick_link_label']) {
                $item = [
                    'linkSuffix' => strtolower('#' . str_replace(' ', '-', $tab['quick_link_label'])),
                    'linkName'   => $tab['quick_link_label']
                ];

                $tab_anchor_links[] = $item;
            }
        }

        return $tab_anchor_links;
    }

    protected function doesPageHaveAnchors($page) {
        $doesPageHaveAnchor = false;

        foreach ($page->tabs[0]->blocks as $block) {
            foreach ($block->data as $blockData) {
                if ($blockData['name'] == 'is_quick_link_enabled' && $blockData['content'] == 1) {
                    $doesPageHaveAnchor = true;
                }
            }
        }

        return $doesPageHaveAnchor;
    }

    protected function getAnchorLinks($page) {
        $anchor_tags = [];

        $orderedBlocks = General::orderDataBy($page->tabs[0]->blocks->toArray(), 'index');

        foreach ($orderedBlocks as $block) {
            foreach ($block['data'] as $blockData) {
                if ($blockData['name'] == 'is_quick_link_enabled' && $blockData['content'] == 1) {
                    $blockTitle = isset($block['data'][5]) && $block['data'][5]['content'] ? $block['data'][5]['content'] : 'Link';

                    $item = [
                        'linkSuffix' => strtolower('#' . str_replace(' ', '-', $blockTitle)),
                        'linkName'   => $blockTitle
                    ];

                    $anchor_tags[] = $item;
                }
            }
        }

        return $anchor_tags;
    }

    /**
     * Sort pages based on menu_order parameter
     * If null used title
     */
    protected function reorderPages() {
        usort($this->pages, function ($a, $b) {

            if (is_null($a['navigation_order']) && is_null($b['navigation_order'])) {
                return $a['title'] < $b['title'] ? -1 : 1;
            }
            if (is_null($a['navigation_order'])) {
                return 1;
            }
            if (is_null($b['navigation_order'])) {
                return -1;
            }
            if ($a['navigation_order'] == $b['navigation_order']) {
                return 0;
            }

            return (intval($a['navigation_order']) < intval($b['navigation_order'])) ? -1 : 1;
        });
    }

    /**
     * Bind data to the view.
     *
     * @param  View $view
     *
     * @return void
     */
    public function compose(View $view) {
        $view->with('pages', $this->pages);
        if ($this->conference) {
            $view->with('registration_enabled', $this->conference->registration_enabled);
            $view->with('login_enabled', $this->conference->login_enabled);
        }
    }

}
