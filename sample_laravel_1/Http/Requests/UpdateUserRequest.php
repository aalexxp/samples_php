<?php

namespace App\Http\Requests;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\UserParametrService;

class UpdateUserRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return  bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return  array
     */
    public function rules()
    {
        $type = $this->get('type');
        $id = $this->route('id');
        return [
            'name' => 'required|max:255',
            'username' => "required|username:{$type},{$id}|max:255",
            'email' => "required|email|unique:users,email,{$id}",
            'signature_id' => 'required'
        ];
    }

    public function validate()
    {
        parent::validate();

        $service = app(UserParametrService::class);

        if (!$service->exists(['id' => $this->route('id')])) {
            throw new NotFoundHttpException('User does not exists');
        }
    }
}
