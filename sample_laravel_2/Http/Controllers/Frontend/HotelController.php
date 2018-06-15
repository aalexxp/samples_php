<?php

namespace App\Http\Controllers\Frontend;

use App\Conference;
use App\Content;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class HotelController extends BaseController
{

    /**
     * HotelController constructor.
     */
    public function __construct() {
    }

    /**
     * Render Hotels index page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Conference $conference) {
        $data = [
            'global'  => Content::GetContent('global', $conference),
            'content' => Content::GetContent('hotel-finder', $conference),
            'hotels'  => $conference->hotels()->with('photo')->get()->toJson(),
        ];

        return $this->getView(
            'hotel-finder',
            $data, $conference
        );
    }

}

