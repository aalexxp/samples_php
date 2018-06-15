<?php

namespace App\Http\Controllers\Frontend;

use App\Conference;
use App\Content;
use App\Http\Controllers\Controller;
use App\Page;
use Illuminate\Support\Facades\Request;
use function MongoDB\BSON\toJSON;


/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class ConferenceController extends Controller
{

    public function index(Conference $conference) {

        $page = Page::with(['tabs', 'tabs.blocks', 'tabs.blocks.data'])
            ->where('conference_id', $conference->id)
            ->where('slug', 'home')
            ->first();

        return $this->getView('cms.custom-page', [
            'global'    => Content::GetContent('global', $conference),
            'content'   => $page->toArray(),
            'page_data' => Content::GetContent($page->slug, $conference),
        ], $conference);
    }

    protected function getView($view, $args = [], Conference $conference) {
        $viewPath = 'frontend.themes.' . $conference->frontend_theme . '.';
        if ((Request::hasHeader('X-App-Type') && Request::header('X-App-Type') == 'gbf-mobile-react-native') || Request::get('mobile_mode')) {
            $viewPath .= 'mobile-view.';
        }

        return view(
            $viewPath . $view,
            $args
        );
    }
}
