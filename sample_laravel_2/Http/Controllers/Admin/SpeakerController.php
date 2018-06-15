<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSpeakerRequest;
use App\Http\Requests\EditSpeakerRequest;
use App\Speaker;
use Illuminate\Support\Facades\Auth;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class SpeakerController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
    }


    public function index() {
        return view('admin.speaker.index');
    }

    /**
     * @param $speaker
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function get(Speaker $speaker) {
        return $speaker;
    }


    public function createPost(CreateSpeakerRequest $r) {
        $speaker = new Speaker;

        $speaker->name          = $r->input('name');
        $speaker->conference_id = Auth::user()->conference->id;
        $speaker->company       = $r->input('company');
        $speaker->company_title = $r->input('company_title');
        $speaker->country       = $r->input('country');
        $speaker->photo         = $r->input('photo') ?: 0;
        $speaker->enabled       = $r->input('enabled') ?: 0;
        $speaker->featured      = $r->input('featured') ?: 0;
        $speaker->type          = $r->input('type');
        $speaker->bio           = $r->input('bio');

        $speaker->save();

        return response()->json(
            [
                'success' => true,
            ]
        );
    }

    public function edit(EditSpeakerRequest $r, Speaker $speaker) {

        $speaker->name          = $r->input('name');
        $speaker->conference_id = Auth::user()->conference->id;
        $speaker->company       = $r->input('company');
        $speaker->company_title = $r->input('company_title');
        $speaker->country       = $r->input('country');
        $speaker->photo         = $r->input('photo') ?: 0;
        $speaker->enabled       = $r->input('enabled') ?: 0;
        $speaker->featured      = $r->input('featured') ?: 0;
        $speaker->type          = $r->input('type');
        $speaker->bio           = $r->input('bio');

        $speaker->save();

        return response()->json(
            [
                'success' => true,
            ]
        );
    }

    public function remove(Speaker $speaker) {
        $success = false;
        if ($speaker) {
            $success = $speaker->delete();
        }
        return response()->json(
            [
                'success' => $success,
            ]
        );
    }


    public function companyList() {
        return Speaker::where('conference_id', Auth::user()->conference->id)->groupBy('company')->get(['company']);
    }

}
