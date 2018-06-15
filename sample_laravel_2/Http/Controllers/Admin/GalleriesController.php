<?php

namespace App\Http\Controllers\Admin;

use App\Gallery;
use App\Http\Requests\Gallery\GalleryRequest;
use Illuminate\Http\Request;

class GalleriesController extends BaseController
{

    public function index() {
        $galleries = Gallery::where('conference_id', $this->getActiveConferenceId())->get()->toArray();

        return view(
            'admin.content.gallery.index',
            [
                'values' => $galleries,
            ]
        );
    }

    public function get(Gallery $gallery) {
        if ($gallery) {
            $gallery->gallery_items = array_map(function ($item) {
                return $item['id'];
            }, $gallery->assets->toArray());

            return response()->json([
                'success' => true,
                'gallery' => $gallery
            ]);
        }

        return response()->json([
            'success' => false,
        ]);
    }

    public function create(GalleryRequest $request) {
        try {
            $gallery = new Gallery();
            $gallery->title = $request->get('title');
            $gallery->description = $request->get('description');
            $gallery->conference_id = $this->getActiveConferenceId();
            $gallery->preview = $request->get('preview');
            $gallery->save();

            $gallery->assets()->sync($request->get('gallery_items'));

            return response()->json([
                'success' => true,
                'gallery' => $gallery
            ]);

        } catch (\Exception $e) {
            $this->logException('GalleriesController * create', $e);
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function update(GalleryRequest $request, $id) {
        if (!$id) {
            return response()->json([
                'success' => false
            ]);
        }
        $gallery = Gallery::find($id);
        if (!$gallery) {
            return response()->json([
                'success' => false
            ]);
        }
        try {
            $gallery->title = $request->get('title');
            $gallery->description = $request->get('description');
            $gallery->preview = $request->get('preview');
            $gallery->save();

            $gallery->assets()->detach();
            $gallery->assets()->sync($request->get('gallery_items'));

            return response()->json([
                'success' => true,
                'gallery' => $gallery
            ]);

        } catch (\Exception $e) {
            $this->logException('GalleriesController * create', $e);
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function delete(Request $request, $id) {
        if (!$id) {
            return response()->json([
                'success' => false
            ]);
        }
        $gallery = Gallery::find($id);
        if (!$gallery) {
            return response()->json([
                'success' => false
            ]);
        }
        $gallery->assets()->detach();
        $gallery->delete();
        return response()->json([
            'success' => true
        ]);
    }
}
