<?php

namespace App\Http\Requests;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\UserParametrService;

class PatchUserRequest extends Request
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
        return [
            'email' => 'unique:users,email|email',
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
