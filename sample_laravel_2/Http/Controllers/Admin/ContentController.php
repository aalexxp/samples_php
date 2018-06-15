<?php

/*
 * Taken from
 * https://github.com/laravel/framework/blob/5.3/src/Illuminate/Auth/Console/stubs/make/controllers/HomeController.stub
 */

namespace App\Http\Controllers\Admin;

use App\ConferenceAsset;
use App\Content;
use App\Http\Requests\Content\ConferenceGlobalContentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ImagickException;
use PHP_ICO;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class ContentController extends BaseController
{

    protected $phpIco;

    public function __construct() {
        $this->middleware('auth');
    }


    public function global() {
        $content = Content::GetContentVue('global', Auth::user()->conference);

        $content['conference_slug'] = Auth::user()->conference->url_slug;

        return view(
            'admin.content.global',
            [
                'content' => $content,
            ]
        );
    }


    public function globalPost(ConferenceGlobalContentRequest $r) {

        $content = [
            'meta_title'              => $r->input('meta_title'),
            'meta_description'        => $r->input('meta_description'),
            'primary_color_1'         => $r->input('primary_color_1'),
            'primary_color_2'         => $r->input('primary_color_2'),
            'primary_text_1'          => $r->input('primary_text_1'),
            'primary_text_2'          => $r->input('primary_text_2'),
            'forum_title'             => $r->input('forum_title'),
            'forum_date'              => $r->input('forum_date'),
            'forum_venue'             => $r->input('forum_venue'),
            'event_logo'              => $r->input('event_logo'),
            'header_banner'           => $r->input('header_banner'),
            'logo_banner'             => $r->input('logo_banner'),
            'header_image'            => $r->input('header_image'),
            'footer_copyright_text'   => $r->input('footer_copyright_text'),
            'footer_background_image' => $r->input('footer_background_image'),
            'footer_left_text'        => $r->input('footer_left_text'),
            'footer_left_link'        => $r->input('footer_left_link'),
            'footer_left_image'       => $r->input('footer_left_image'),
            'footer_right_text'       => $r->input('footer_right_text'),
            'footer_right_link'       => $r->input('footer_right_link'),
            'footer_right_image'      => $r->input('footer_right_image'),
            'footer_social_text'      => $r->input('footer_social_text'),
            'footer_social_link_fb'   => $r->input('footer_social_link_fb'),
            'footer_social_link_tw'   => $r->input('footer_social_link_tw'),
            'footer_social_link_ln'   => $r->input('footer_social_link_ln'),
            'footer_social_link_yt'   => $r->input('footer_social_link_yt'),
            'footer_app_google'       => $r->input('footer_app_google'),
            'footer_app_apple'        => $r->input('footer_app_apple'),
            'footer_app_heading'      => $r->input('footer_app_heading'),
        ];

        $save = Content::SaveContent('global', $content);

        return response()->json(
            [
                'success' => true,
                'updated' => $save,
            ]
        );
    }


    public function mediaManager() {
        return view('admin.content.media-manager');
    }


    public function mediaManagerUpload(Request $r) {
        $conference = Auth::user()->conference;
        $type = $r->input('type');
        $filePath = '/storage/cms/' . $conference->url_slug . '/' . $type . '/';
        $storePath = 'app/public/cms/' . $conference->url_slug . '/' . $type;

        list($width, $height) = getimagesize($r->file);

        $fileName = $this->generateUniqueFileName($r->file->getClientOriginalName(), $storePath);

        $asset = new ConferenceAsset;

        $asset->filename        = $r->file->getClientOriginalName();
        $asset->size            = $r->file->getClientSize();
        $asset->width           = $width;
        $asset->height          = $height;
        $asset->path            = $filePath . $fileName;
        $asset->type            = $type;
        $asset->conference_id   = $conference->id;

        $asset->save();

        $r->file->move(storage_path($storePath), $fileName);


        if ($type == 'file') {
            try {
                chmod(storage_path($storePath . '/' . $fileName), 0777);
                $pdf = new \Spatie\PdfToImage\Pdf(storage_path($storePath . '/' . $fileName));
                $pdf->saveImage(storage_path($storePath . '/' . $fileName . "_THUMB.png"));
            } catch (ImagickException $e) {
                logger('ContentController * mediaManagerUpload * ImagickException', [
                    storage_path($storePath . '/' . $fileName),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ]);
            }
        }

        return response()->json(
            [
                "uploaded" => true,
                "asset"    => $asset,
            ]
        );
    }

    protected function generateUniqueFileName($fileName, $path, $step = 0) {
        $newFileName = $step ? $step . '_' . $fileName : $fileName;
        if (file_exists(storage_path($path . '/' . $newFileName))) {
            return $this->generateUniqueFileName($fileName, $path, ++$step);
        }
        logger('NEW FILE NAME ', [$newFileName]);
        return $newFileName;
    }


    public function mediaManagerSaveAlt(Request $r) {

        $asset = ConferenceAsset::find($r->input('id'));

        $asset->alt_text = $r->input('alt_text');
        $asset->save();

        //Make the user think there's some really complex stuff going on
        sleep(1);

        return response()->json(
            [
                'success' => true,
            ]
        );

    }

    /**
     * @param ConferenceAsset $asset
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mediaManagerRemoveAsset(ConferenceAsset $asset) {
        if ($asset) {
            $asset->delete();

            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ]);
        }
    }


    public function setFavicon(Request $request) {
        $conference = Auth::user()->conference;

        $sizes = array(
            array(16, 16),
            array(24, 24),
            array(32, 32),
            array(48, 48),
        );

        $arr = explode('/', $request->favicon_path);

        $imageName = $arr[count($arr) - 1];

        $path = storage_path('app/public/cms/' . $conference->url_slug . '/images/' . $imageName);
        $destination = storage_path('app/public/cms/' . $conference->url_slug . '/images/favicon.ico');;

        $this->phpIco = new PHP_ICO($path, $sizes);
        $this->phpIco->save_ico($destination);

        return response()->json(['success'      => true,
                                 'favicon_path' => '/storage/cms/' . $conference->url_slug . '/images/favicon.ico'
        ]);
    }
}
