<?php

namespace App\Http\Controllers\Frontend\Cms;

use App\Conference;
use App\ConferenceAsset;
use App\Content;
use App\Helpers\General;
use App\Http\Controllers\Frontend\BaseController as ConferenceBaseController;
use App\Page;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class BaseController extends ConferenceBaseController
{

    public function single(Conference $conference, $customPage) {

        //Disable double index
        if ($customPage == 'home') {
            return response(404);
        }

        $page = Page::with(['tabs', 'tabs.blocks', 'tabs.blocks.data'])
            ->where('conference_id', $conference->id)
            ->where(function ($q) use ($customPage) {
                $q->where('id', $customPage)
                    ->orWhere('slug', $customPage);
            })
            ->first();

        if (!$page) {
            return response()->redirectToRoute(General::conferenceRoute('frontend.index'));
        }

        return $this->getView('cms.custom-page', [
            'global'    => Content::GetContent('global', $conference),
            'content'   => $page->toArray(),
            'page_data' => Content::GetContent($page->slug, $conference),
        ], $conference);
    }

    public function getAsset(Conference $conference, ConferenceAsset $asset) {
        if (!$asset) {
            return response()->json([
                'success' => false
            ], 422);
        }

        return response()->json([
            'success' => true,
            'asset'   => $asset
        ]);
    }

}
