<?php namespace App\Http\Requests\Delegate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PutDelegateRequest extends FormRequest
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
    public function rules() {
        $conferenceID = Auth::user()->getActiveConferenceId();
        return [
            'email' => [
                'nullable',
                'email',
                Rule::unique('delegates')->where(function ($query) use ($conferenceID) {
                    $query->where('conference_id', $conferenceID);
                })
            ],
            'uic'   => [
                'nullable',
                Rule::unique('delegates')->where(function ($query) use ($conferenceID) {
                    $query->where('conference_id', $conferenceID);
                })
            ],
        ];
    }
}
