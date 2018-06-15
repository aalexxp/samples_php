<?php

/*
 * Taken from
 * https://github.com/laravel/framework/blob/5.3/src/Illuminate/Auth/Console/stubs/make/controllers/HomeController.stub
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Delegate;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class AdminController extends Controller
{
    public function __construct() {
        $this->middleware('auth', [
            'except' => 'delegateView'
        ]);
    }

    /**
     * Show the application dashboard.
     *
     * @param int $id
     *
     * @return \Response
     */
    public function switchConference($id) {
        Auth::user()->switchConference($id);

        return back();
    }

    public function index() {
        return view('admin.home');
    }

    public function delegateView(Request $request, $id) {
        Auth::guard('delegate')->loginUsingId($id, true);

        return redirect()->route('frontend.dashboard', ['conference' => Auth::user()->conference->url_slug]);
    }
}
