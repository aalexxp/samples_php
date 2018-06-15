<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\RegformField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegformController extends Controller
{
    public function get() {

        $fields = RegformField::where('enabled', 1)->where('conference_id', Auth::user()->current_conference)->get()->toJson();

        return view('admin.regform.index', [
            'fields' => $fields
        ]);
    }

    public function post(Request $r) {

        $fields = $r->all();

        RegformField::where('conference_id', Auth::user()->current_conference)->delete();

        foreach ($fields as $k => $field) {
            $r = RegformField::firstOrNew(['name' => $field['name'], 'conference_id' => Auth::user()->current_conference]);
            $r->fill($field);
            $r->order = $k;
            $r->enabled = 1;
            $r->default = isset($field['default']) ? 1 : null;
            $r->multiple = isset($field['multiple']) ? $field['multiple'] : null;
            $r->values = isset($field['values']) ? json_encode($field['values']) : null;
            $r->save();
        }

        return response()->json([
            'success' => true
        ]);
    }
}
