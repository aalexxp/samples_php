<?php namespace App\Http\Requests\Delegate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PatchDelegateRequest extends FormRequest
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
        $user = Auth::user();
        $conferenceID = $user->getActiveConferenceId();
        return [
            'email' => [
                'nullable',
                'email',
                Rule::unique('delegates')->where(function ($query) use ($conferenceID) {
                    $query->where('conference_id', $conferenceID)->where('id', '!=', $this->id);
                })
            ]
        ];
    }
}