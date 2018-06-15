<?php

namespace App\Http\Controllers\Admin\Content;

use App\Content;
use App\Gallery;
use App\Http\Controllers\Admin\BaseController as AdminBaseController;
use App\Http\Requests\Content\BaseGalleryContentRequest;
use App\Http\Requests\Content\BaseHomeContentRequest;
use App\Http\Requests\Content\BaseLoginRegisterContentRequest;
use App\Http\Requests\Content\BaseProgrammeContentRequest;
use App\Http\Requests\Content\BaseSpeakersContentRequest;
use App\PageAsset;
use App\Speaker;
use Illuminate\Support\Facades\Auth;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class BaseController extends AdminBaseController
{

    public function __construct() {
        $this->middleware('auth');
    }


    public function home() {
        $content = Content::GetContentVue('home', Auth::user()->conference);

        return view(
            'admin.content.base.home',
            [
                'content' => $content,
            ]
        );
    }


    public function homePost(BaseHomeContentRequest $r) {

        $content = [
            'meta_title'               => $r->input('meta_title'),
            'meta_description'         => $r->input('meta_description'),
            'countdown_enabled'        => $r->input('countdown_enabled'),
            'countdown_heading'        => $r->input('countdown_heading'),
            'countdown_background'     => $r->input('countdown_background'),
            'countdown_time'           => $r->input('countdown_time'),
            'countdown_date'           => $r->input('countdown_date'),
            'numbers_enabled'          => $r->input('numbers_enabled'),
            'numbers_heading'          => $r->input('numbers_heading'),
            'numbers_background'       => $r->input('numbers_background'),
            'numbers_figure_1_value'   => $r->input('numbers_figure_1_value'),
            'numbers_figure_1_percent' => $r->input('numbers_figure_1_percent'),
            'numbers_figure_1_value'   => $r->input('numbers_figure_1_value'),
            'numbers_figure_1_percent' => $r->input('numbers_figure_1_percent'),
            'numbers_figure_1_heading' => $r->input('numbers_figure_1_heading'),
            'numbers_figure_1_value'   => $r->input('numbers_figure_1_value'),
            'numbers_figure_1_percent' => $r->input('numbers_figure_1_percent'),
            'numbers_figure_2_heading' => $r->input('numbers_figure_2_heading'),
            'numbers_figure_2_value'   => $r->input('numbers_figure_2_value'),
            'numbers_figure_2_percent' => $r->input('numbers_figure_2_percent'),
            'numbers_figure_3_heading' => $r->input('numbers_figure_3_heading'),
            'numbers_figure_3_value'   => $r->input('numbers_figure_3_value'),
            'numbers_figure_3_percent' => $r->input('numbers_figure_3_percent'),
            'numbers_figure_4_heading' => $r->input('numbers_figure_4_heading'),
            'numbers_figure_4_value'   => $r->input('numbers_figure_4_value'),
            'numbers_figure_4_percent' => $r->input('numbers_figure_4_percent'),
            'numbers_figure_5_heading' => $r->input('numbers_figure_5_heading'),
            'numbers_figure_5_value'   => $r->input('numbers_figure_5_value'),
            'numbers_figure_5_percent' => $r->input('numbers_figure_5_percent'),
            'speakers_enabled'         => $r->input('speakers_enabled'),
            'speakers_heading'         => $r->input('speakers_heading'),
            'speakers_copy'            => $r->input('speakers_copy'),
            'speakers_button_copy'     => $r->input('speakers_button_copy'),
            'speakers_button_link'     => $r->input('speakers_button_link'),
            'programme_enabled'        => $r->input('programme_enabled'),
            'programme_heading'        => $r->input('programme_heading'),
            'programme_copy'           => $r->input('programme_copy'),
            'programme_copy_2'         => $r->input('programme_copy_2'),
            'programme_button_copy'    => $r->input('programme_button_copy'),
            'programme_button_link'    => $r->input('programme_button_link'),
            'push_enabled'             => $r->input('push_enabled'),
            'push_heading'             => $r->input('push_heading'),
            'push_background'          => $r->input('push_background'),
            'push_left_image'          => $r->input('push_left_image'),
            'push_left_heading'        => $r->input('push_left_heading'),
            'push_left_copy'           => $r->input('push_left_copy'),
            'push_left_link_copy'      => $r->input('push_left_link_copy'),
            'push_left_link_link'      => $r->input('push_left_link_link'),
            'push_right_image'         => $r->input('push_right_image'),
            'push_right_heading'       => $r->input('push_right_heading'),
            'push_right_copy'          => $r->input('push_right_copy'),
            'push_right_link_copy'     => $r->input('push_right_link_copy'),
            'push_right_link_link'     => $r->input('push_right_link_link'),
        ];

        $save = Content::SaveContent('home', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }


    public function speakers() {
        $content = Content::GetContentVue('speakers', Auth::user()->conference);
        $speakers = Speaker::with('photo')->where('conference_id', $this->getActiveConferenceId())
            ->where('enabled', true)
            ->get()->groupBy('type');
        $confirmed = $speakers['1'] ?? [];
        $featured = $speakers['1'] ?? [];
        $prev = $speakers['2'] ?? [];

        return view(
            'admin.content.base.speakers',
            [
                'content'   => $content,
                'featured'  => \GuzzleHttp\json_encode($featured),
                'confirmed' => \GuzzleHttp\json_encode($confirmed),
                'prev'      => \GuzzleHttp\json_encode($prev),
            ]
        );
    }


    public function speakersPost(BaseSpeakersContentRequest $r) {

        $content = [
            'meta_title'            => $r->input('meta_title'),
            'meta_description'      => $r->input('meta_description'),
            'header_image_override' => $r->input('header_image_override'),
            'menu_title'            => $r->input('menu_title'),
            'navigation_order'      => $r->input('navigation_order'),
            'page_heading'          => $r->input('page_heading'),
            'page_subheading'       => $r->input('page_subheading'),
            'confirmed_heading'     => $r->input('confirmed_heading'),
            'previous_heading'      => $r->input('previous_heading'),
            'confirmed_text'        => $r->input('confirmed_text'),
            'previous_text'         => $r->input('previous_text'),
            'featured_heading'      => $r->input('featured_heading'),
            'featured_text'         => $r->input('featured_text'),
        ];

        if (is_array($r->input('featured_speakers'))) {
            $content['featured_speakers'] = \GuzzleHttp\json_encode($r->input('featured_speakers'));
        }

        if (is_array($r->input('confirmed_speakers'))) {
            $content['confirmed_speakers'] = \GuzzleHttp\json_encode($r->input('confirmed_speakers'));
        }

        if (is_array($r->input('previous_speakers'))) {
            $content['previous_speakers'] = \GuzzleHttp\json_encode($r->input('previous_speakers'));
        }

        $save = Content::SaveContent('speakers', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }


    public function programme() {
        $content = Content::GetContentVue('programme', Auth::user()->conference);

        return view(
            'admin.content.base.programme',
            [
                'content' => $content,
            ]
        );
    }


    public function programmePost(BaseProgrammeContentRequest $r) {

        $content = [
            'meta_title'            => $r->input('meta_title'),
            'meta_description'      => $r->input('meta_description'),
            'header_image_override' => $r->input('header_image_override'),
            'menu_title'            => $r->input('menu_title'),
            'navigation_order'      => $r->input('navigation_order'),
            'page_heading'          => $r->input('page_heading'),
            'page_subheading'       => $r->input('page_subheading'),
        ];

        $save = Content::SaveContent('programme', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }


    public function register() {
        $content = Content::GetContentVue('register', Auth::user()->conference);

        return view(
            'admin.content.base.register',
            [
                'content' => $content,
            ]
        );
    }


    public function registerPost(BaseLoginRegisterContentRequest $r) {

        $content = [
            'meta_title'             => $r->input('meta_title'),
            'meta_description'       => $r->input('meta_description'),
            'header_image_override'  => $r->input('header_image_override'),
            'menu_title'             => $r->input('menu_title'),
            'navigation_order'       => $r->input('navigation_order'),
            'page_heading'           => $r->input('page_heading'),
            'page_subheading'        => $r->input('page_subheading'),
            'popup_uic_error'        => $r->input('popup_uic_error'),
            'popup_register_success' => $r->input('popup_register_success'),
        ];

        $save = Content::SaveContent('register', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }


    public function login() {
        $content = Content::GetContentVue('login', Auth::user()->conference);

        return view(
            'admin.content.base.login',
            [
                'content' => $content,
            ]
        );
    }


    public function loginPost(BaseLoginRegisterContentRequest $r) {

        $content = [
            'meta_title'            => $r->input('meta_title'),
            'meta_description'      => $r->input('meta_description'),
            'header_image_override' => $r->input('header_image_override'),
            'menu_title'            => $r->input('menu_title'),
            'navigation_order'      => $r->input('navigation_order'),
            'page_heading'          => $r->input('page_heading'),
            'page_subheading'       => $r->input('page_subheading'),
        ];

        $save = Content::SaveContent('login', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }

    public function knowledgeHub() {
        $content = Content::GetContentVue('knowledge-hub', Auth::user()->conference);
        $content->put('gallery_items', PageAsset::where('conference_id', $this->getActiveConferenceId())
            ->where('page', 'knowledge-hub')
            ->where('type', 'gallery')->get());
        $content->put('report_items', PageAsset::where('conference_id', $this->getActiveConferenceId())
            ->where('page', 'knowledge-hub')
            ->where('type', 'report')->get());
        $content->put('press_release_items', PageAsset::where('conference_id', $this->getActiveConferenceId())
            ->where('page', 'knowledge-hub')
            ->where('type', 'press_release')->get());


        $galleries = Gallery::with('preview')->where('conference_id', $this->getActiveConferenceId())->get()->toArray();

        return view(
            'admin.content.base.knowledge-hub',
            [
                'content'   => $content,
                'galleries' => $galleries,
            ]
        );
    }


    public function knowledgeHubPost(BaseGalleryContentRequest $r) {

        $content = [
            'meta_title'            => $r->input('meta_title'),
            'meta_description'      => $r->input('meta_description'),
            'header_image_override' => $r->input('header_image_override'),
            'menu_title'            => $r->input('menu_title'),
            'navigation_order'      => $r->input('navigation_order'),
            'page_heading'          => $r->input('page_heading'),
            'page_subheading'       => $r->input('page_subheading'),
            'gallery_enabled'       => $r->input('gallery_enabled'),
            'report_enabled'        => $r->input('report_enabled'),
            'press_release_enabled' => $r->input('press_release_enabled'),
        ];

        PageAsset::addAssets('knowledge-hub', $this->getActiveConferenceId(), $r->input('gallery_items'), 'gallery');
        PageAsset::addAssets('knowledge-hub', $this->getActiveConferenceId(), $r->input('report_items'), 'report');
        PageAsset::addAssets('knowledge-hub', $this->getActiveConferenceId(), $r->input('press_release_items'), 'press_release');

        $save = Content::SaveContent('knowledge-hub', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }

    /**
     * @return mixed
     */
    public function contact() {
        $content = Content::GetContentVue('contact', Auth::user()->conference);

        return view(
            'admin.content.base.contact',
            [
                'content' => $content,
            ]
        );
    }


    /**
     *
     * @param BaseLoginRegisterContentRequest $r
     *
     * @return mixed
     */
    public function contactPost(BaseLoginRegisterContentRequest $r) {

        $content = [
            'meta_title'            => $r->input('meta_title'),
            'meta_description'      => $r->input('meta_description'),
            'header_image_override' => $r->input('header_image_override'),
            'menu_title'            => $r->input('menu_title'),
            'navigation_order'      => $r->input('navigation_order'),
            'page_heading'          => $r->input('page_heading'),
            'page_subheading'       => $r->input('page_subheading'),
            'gallery_enabled'       => $r->input('gallery_enabled'),
            'report_enabled'        => $r->input('report_enabled'),
            'press_release_enabled' => $r->input('press_release_enabled'),
        ];
        $nature_of_enquiry = $r->input('nature_of_enquiry');
        if ($nature_of_enquiry && is_array($nature_of_enquiry)) {
            $content['nature_of_enquiry'] = \GuzzleHttp\json_encode($nature_of_enquiry);
        }

        $save = Content::SaveContent('contact', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function mediaRegistration() {
        $content = Content::GetContentVue('media-registration', Auth::user()->conference);

        return view(
            'admin.content.base.media-registration',
            [
                'content' => $content,
            ]
        );
    }


    /**
     * @param BaseLoginRegisterContentRequest $r
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mediaRegistrationPost(BaseLoginRegisterContentRequest $r) {

        $content = [
            'meta_title'            => $r->input('meta_title'),
            'meta_description'      => $r->input('meta_description'),
            'header_image_override' => $r->input('header_image_override'),
            'menu_title'            => $r->input('menu_title'),
            'navigation_order'      => $r->input('navigation_order'),
            'page_heading'          => $r->input('page_heading'),
            'page_subheading'       => $r->input('page_subheading'),
            'gallery_enabled'       => $r->input('gallery_enabled'),
            'report_enabled'        => $r->input('report_enabled'),
            'press_release_enabled' => $r->input('press_release_enabled'),
        ];

        $save = Content::SaveContent('media-registration', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function dashboard() {
        $content = Content::GetContentVue('dashboard', Auth::user()->conference);

        return view(
            'admin.content.base.dashboard',
            [
                'content' => $content,
            ]
        );
    }


    /**
     * @param BaseLoginRegisterContentRequest $r
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboardPost(BaseLoginRegisterContentRequest $r) {

        $content = [
            'meta_title'            => $r->input('meta_title'),
            'meta_description'      => $r->input('meta_description'),
            'header_image_override' => $r->input('header_image_override'),
//			'menu_title'            => $r->input( 'menu_title' ),
//			'navigation_order'      => $r->input( 'navigation_order' ),
            'page_heading'          => $r->input('page_heading'),
            'page_subheading'       => $r->input('page_subheading'),
            'profile_content'       => $r->input('profile_content'),
            'planner_content'       => $r->input('planner_content'),
        ];

        $save = Content::SaveContent('dashboard', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }

}
