<?php


/*
 * Taken from
 * https://github.com/laravel/framework/blob/5.3/src/Illuminate/Auth/Console/stubs/make/controllers/HomeController.stub
 */

namespace App\Http\Controllers\Admin;

use App\Content;
use App\Hotel;
use App\Http\Requests\Content\BaseHomeContentRequest;
use App\Http\Requests\Hotel\HotelCreateRequest;
use Illuminate\Support\Facades\Auth;

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
        $this->middleware('auth');
    }

    /**
     * Render Hotels index page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {

        $hotels = Hotel::all()->toJson();

        return view('admin.hotel.index', ['hotels' => $hotels]);
    }

    /**
     * Store hotel from request data
     *
     * @param HotelCreateRequest $r
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(HotelCreateRequest $r) {

        $hotel = Hotel::createUpdate($r);

        return response()->json(
            [
                'success' => true,
                'hotel'   => $hotel
            ]
        );
    }

    /**
     * Render update hotel page
     *
     * @param Hotel $hotel
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update(Hotel $hotel) {
        return view('admin.hotel.update', [
            'hotel' => $hotel
        ]);
    }

    /**
     * Update hotel from request data
     *
     * @param HotelCreateRequest $r
     * @param Hotel $hotel
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function patch(HotelCreateRequest $r, Hotel $hotel) {

        $hotel = Hotel::createUpdate($r, $hotel);

        return response()->json(
            [
                'success' => true,
                'hotel'   => $hotel
            ]
        );
    }

    /**
     * Delete hotel
     *
     * @param Hotel $hotel
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Hotel $hotel) {

        if ($hotel) {
            $hotel->delete();
        }

        return response()->json(
            [
                'success' => true,
            ]
        );
    }


    /**
     * Return all hotels. Ajax Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllHotelsAjax() {

        return response()->json(Hotel::all());
    }


    public function contentHotel() {
        $conference = Auth::user()->conference;
        $content = Content::GetContentVue('hotel-finder', $conference);
        $hotels = Hotel::with('photo')->get()->toJson();

        $hotelsOrdered = $conference->hotels()->with('photo')->get()->toJson();

        $pages = $conference->pages->toJson();

        return view(
            'admin.content.base.hotel-finder',
            [
                'content'        => $content,
                'hotels'         => $hotels,
                'ordered_hotels' => $hotelsOrdered,
                'customPages'    => $pages
            ]
        );
    }


    public function contentHotelSave(BaseHomeContentRequest $r) {
        $conference = Auth::user()->conference;
        $content = [
            'meta_title'            => $r->input('meta_title'),
            'meta_description'      => $r->input('meta_description'),
            'header_image_override' => $r->input('header_image_override'),
            'menu_title'            => $r->input('menu_title'),
            'navigation_order'      => $r->input('navigation_order'),
            'page_heading'          => $r->input('page_heading'),
            'page_subheading'       => $r->input('page_subheading'),
            'confirmed_heading'     => $r->input('confirmed_heading'),
            'hotel_finder_enabled'  => $r->input('hotel_finder_enabled'),
            'lat'                   => $r->input('lat'),
            'lon'                   => $r->input('lon'),
            'zoom'                  => $r->input('zoom'),
            'parent_id'             => $r->input('parent_id'),
        ];

        if (is_array($r->input('ordered_hotels'))) {
            $hotelsFiltered = [];
            foreach ($r->input('ordered_hotels') as $hotel) {
                $hotelsFiltered[$hotel['id']] = [
                    'distance' => isset($hotel['pivot']) ? $hotel['pivot']['distance'] : ''
                ];
            }
            $conference->hotels()->sync([]);
            $conference->hotels()->sync($hotelsFiltered);
        }

        $save = Content::SaveContent('hotel-finder', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }

}

