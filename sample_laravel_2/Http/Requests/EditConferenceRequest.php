<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class EditConferenceRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request) {
        return [
            'name'           => 'required|unique:conferences,id,' . $request->get('id'),
            'url_slug'       => 'required|unique:conferences,id,' . $request->get('id'),
            'admin_email'    => 'required|email',
            'no_reply_email' => 'required|email',
            'bbc_email'      => 'email',
            'admin_theme'    => 'required',
            'frontend_theme' => 'required',
        ];
    }
}
