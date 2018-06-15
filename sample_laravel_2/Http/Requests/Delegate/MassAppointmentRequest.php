<?php namespace App\Http\Requests\Delegate;

use Illuminate\Foundation\Http\FormRequest;

class MassAppointmentRequest extends FormRequest
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
        return [
            'day'       => 'required',
            'title'     => 'required',
            'time_from' => 'required',
            'time_to'   => 'required',
        ];
    }
}