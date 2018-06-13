<?php

namespace App\Http\Requests;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\TrackService;

class UpdateTrackRequest extends Request
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
            'name' => 'required|max:255'
        ];
    }

    public function validate()
    {
        parent::validate();

        $service = app(TrackService::class);

        if (!$service->exists(['id' => $this->route('id')])) {
            throw new NotFoundHttpException('Track does not exists');
        }
    }
}
