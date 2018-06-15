<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Content;
use App\Helpers\General;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\CreatePageRequest;
use App\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BaseController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
    }


    /**
     * Render new cms page builder
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function createNewPage() {
        $conferenceId = Auth::user()->getActiveConferenceId();
        $pageList = Page::where('conference_id', $conferenceId)->get()->toJson();
        return view('admin.cms.create-new-page', compact('pageList'));
    }


    /**
     * Render update cms page
     *
     * @param $idOrSlug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function updatePage($idOrSlug) {
        $conferenceId = Auth::user()->getActiveConferenceId();
        $page = Page::with(['tabs', 'tabs.blocks', 'tabs.blocks.data'])
            ->where('conference_id', $conferenceId)
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();
        if (!$page) {
            return response()->redirectToRoute('admin.cms.create');
        }

        $meta = Content::GetContentVue($page->slug, Auth::user()->conference);

        $page['meta_data'] = $meta;

        $pageList = \App\Page::all()->toJson();

        return view('admin.cms.update-page', [
            'content'  => $page->toJson(),
            'pageList' => $pageList
        ]);
    }


    /**
     * Save new cms page to DB
     *
     * @param CreatePageRequest $request
     *
     * @return mixed
     */
    public function savePage(CreatePageRequest $request) {
        $conferenceId = Auth::user()->getActiveConferenceId();
        $data = $request->all();
        try {
            $page = Page::createPage([
                'title'         => $data['page_title'],
                'conference_id' => $conferenceId,
                'type'          => $data['page_type'],
                'parent_id'     => (isset($data['parent_page_id']) && $data['parent_page_id'] != -1) ? $data['parent_page_id'] : null,
                'slug'          => isset($data['page_slug']) ? $data['page_slug'] : false,
                'published'     => isset($data['page_is_published']) ? $data['page_is_published'] : false
            ]);

            if (isset($data['meta_data'])) {
                $save = Content::SaveContent($page->slug, $data['meta_data']);
            }

            $page->storeContent($data);

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            logger('Page Save Exception ', [$e->getMessage(), $e->getFile(), $e->getLine()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.'
            ], 422);
        }
    }


    /**
     * Update cms page in DB
     *
     * @param CreatePageRequest $request
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function patchPage(CreatePageRequest $request, $id) {
        $conferenceId = Auth::user()->getActiveConferenceId();
        $data = $request->all();
        try {
            $page = Page::getUpdatePage([
                'idOrSlug'      => $id,
                'title'         => $data['page_title'],
                'conference_id' => $conferenceId,
                'type'          => $data['page_type'],
                'parent_id'     => (isset($data['parent_page_id']) && $data['parent_page_id'] != -1) ? $data['parent_page_id'] : null,
                'slug'          => isset($data['page_slug']) ? $data['page_slug'] : false,
                'published'     => isset($data['page_is_published']) ? $data['page_is_published'] : false
            ]);

            if (isset($data['meta_data'])) {
                Content::SaveContent($page->slug, $data['meta_data']);
            }

            $page->storeContent($data);

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            logger('Page Save Exception ', [$e->getMessage(), $e->getFile(), $e->getLine()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.'
            ], 422);
        }
    }

    /**
     * Drop page by slug or id
     *
     * @param $idOrSlug
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePage($idOrSlug) {
        $conferenceId = Auth::user()->getActiveConferenceId();
        $page = Page::where('conference_id', $conferenceId)
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();
        if ($page) {
            $page->delete();
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }
    }

    /**
     * Trigger published status
     *
     * @param Request $request
     * @param $idOrSlug
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statusTrigger(Request $request, $idOrSlug) {

        $conferenceId = Auth::user()->getActiveConferenceId();

        $page = Page::where('conference_id', $conferenceId)
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();

        if ($page) {
            $page->published = !$page->published;
            $page->save();
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }
    }

    /**
     * Retrieve assets list by type
     *
     * @param $type
     *
     * @return string
     */
    function getAssets($type) {
        $images = General::ImageCollectionToSelect2(Auth::user()->conference->assets->where('type', $type));

        return $images;
    }

    /**
     *
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function listPages() {
        $conferenceId = Auth::user()->getActiveConferenceId();
        $pages = Page::with(['conference', 'parent'])->where('conference_id', $conferenceId)->get();

        return view('admin.cms.page-list', ['content' => $pages->toJson()]);
    }


    function listPagesAjax() {
        $conferenceId = Auth::user()->getActiveConferenceId();
        $pages = Page::with(['conference', 'parent'])->where('conference_id', $conferenceId)->get();

        return response()->json($pages);
    }

}
