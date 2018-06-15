<?php

/*
 * Taken from
 * https://github.com/laravel/framework/blob/5.3/src/Illuminate/Auth/Console/stubs/make/controllers/HomeController.stub
 */

namespace App\Http\Controllers\Admin;

use App\Conference;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateConferenceRequest;
use App\Http\Requests\EditConferenceRequest;
use App\Page;
use App\PageTab;
use Illuminate\Support\Facades\Auth;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class ConferenceController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
    }


    public function index() {
        return view('admin.conference.index');
    }

    /**
     * @param $conference
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function get(Conference $conference) {
        return $conference;
    }

    public function createPost(CreateConferenceRequest $r) {
        $conf = new Conference;

        $conf->name                 = $r->input('name');
        $conf->admin_theme          = $r->input('admin_theme');
        $conf->frontend_theme       = $r->input('frontend_theme');
        $conf->url_slug             = $r->input('url_slug');
        $conf->admin_email          = $r->input('admin_email');
        $conf->bbc_email            = $r->input('bbc_email');
        $conf->no_reply_email       = $r->input('no_reply_email');
        $conf->enabled              = 1;
        $conf->published            = 0;
        $conf->registration_enabled = $r->input('registration_enabled');
        $conf->login_enabled        = $r->input('login_enabled');

        $conf->save();

        $homepage = new Page;
        $homepage->conference_id    = $conf->id;
        $homepage->title            = $conf->name;
        $homepage->slug             = 'home';
        $homepage->type             = 'content_page';
        $homepage->published        = 1;

        $homepage->save();

        $home_tab = new PageTab;

        $home_tab->page_id = $homepage->id;
        $home_tab->index = 0;
        $home_tab->quick_link_enabled = 0;

        $home_tab->save();

        Auth::user()->switchConference($conf->id);

        return response()->json(
            [
                'success' => true,
            ]
        );
    }

    public function edit(EditConferenceRequest $r, Conference $conference) {
        $conference->name                   = $r->input('name');
        $conference->frontend_theme         = $r->input('frontend_theme');
        $conference->admin_theme            = $r->input('admin_theme');
        $conference->url_slug               = $r->input('url_slug');
        $conference->admin_email            = $r->input('admin_email');
        $conference->bbc_email              = $r->input('bbc_email');
        $conference->no_reply_email         = $r->input('no_reply_email');
        $conference->registration_enabled   = $r->input('registration_enabled');
        $conference->login_enabled          = $r->input('login_enabled');

        $conference->save();

        return response()->json(
            [
                'success' => true,
            ]
        );
    }


    public function editPost(CreateConferenceRequest $r, $id) {
        return response()->json(
            [
                'success' => true,
            ]
        );
    }


    public function delete($id) {
        return back();
    }
}
