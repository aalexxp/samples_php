<?php

namespace App\Http\Controllers;

use App;
use App\Models\CallCenter\ManageNumber;
use App\Models\Comms\Signature;
use App\Models\Interfaces\IsTaggableInterface;
use App\Services\TagService;
use DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class Controller extends BaseController
{

    const DEFAULT_ELEMENTS_PER_PAGE = 20;
    const DEFAULT_SPA_ELEMENTS_PER_PAGE = 3;
    const DEFAULT_NEW_SPA_ELEMENTS_PER_PAGE = 100;
    const DEFAULT_SPA_ELEMENTS_PER_PAGE_EXPANDED = 10;
    const ERROR_RESPONSE_CODE = 400; // 400 - Bad Request
    const DEFAULT_BUCKET = 'whichreapublic';
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $pageType = null;
    protected $methodType = null;
    protected $embedded_page = null;
    protected $page_level_2 = null;

    public function __construct(App\Services\SimpleTemplateService $simpleTemplateService)
    {
        if (App::environment('local')) {
            // The environment is local
            DB::enableQueryLog();
        }

        if (!empty($this->pageType)) {
            view()->share('page_type', $this->pageType);
        }

        if (!empty($this->embedded_page)) {
            view()->share('embedded_page', $this->embedded_page);
        }

        if (!empty($this->page_level_2)) {
            view()->share('page_level_2', $this->page_level_2);
        }

        view()->share('sms_templates', $simpleTemplateService->getSmsTemplates());
        view()->share('comms_signatures', Signature::all());
        view()->share('from_numbers', ManageNumber::all());

        // Create container singletons for filters
        app()->singleton('AllCategories', function () {
            return Cache::remember('AllCategories', 120, function () {
                return \App\Models\Category::all(['id', 'name']);
            });
        });
        app()->singleton('AllUsers', function () {
            return Cache::remember('AllUsers', 120, function () {
                return \App\Models\User::all(['id', 'name']);
            });
        });
    }

    protected function syncTags(IsTaggableInterface $entity, Request $request, $recordType)
    {
        (new TagService())->syncTagsWithEntity($entity, $request->get('tags', []), $recordType);
    }

    public function flashSuccess($message, $redirectTo = null)
    {
        \Session::flash('success', $message);
        if ($redirectTo) {
            return redirect($redirectTo);
        }
        return null;
    }

    public function flashInfo($message, $redirectTo = null)
    {
        \Session::flash('info', $message);
        if ($redirectTo) {
            return redirect($redirectTo);
        }
        return null;
    }

    public function flashError($message, $redirectTo = null)
    {
        \Session::flash('error', $message);

        \Log::error("FLASH_ERROR: " . $message . ($redirectTo ? "; REDIRECT: " . $redirectTo : ''));

        if ($redirectTo) {
            return redirect($redirectTo);
        }
        return null;
    }

    public function flashWarning($message, $redirectTo = null)
    {
        \Session::flash('warning', $message);
        if ($redirectTo) {
            return redirect($redirectTo);
        }
        return null;
    }

    public function jsonError($message, $returnData = null)
    {
        $data = [
            'result' => 'ERROR',
            'message' => $message
        ];

        if ($returnData) {
            $data['data'] = $returnData;
        }

        \Log::error("JSON_ERROR: " . $message);

        return new JsonResponse($data, self::ERROR_RESPONSE_CODE);
    }

    public function jsonOk($message, $returnData = null)
    {
        $data = [
            'result' => 'OK',
            'message' => $message
        ];

        if ($returnData) {
            $data['data'] = $returnData;
        }

        return new JsonResponse($data);
    }

    public function jsonWarning($message, $returnData = null)
    {
        $data = [
            'result' => 'WARNING',
            'message' => $message
        ];

        if ($returnData) {
            $data['data'] = $returnData;
        }

        return new JsonResponse($data);
    }

    public function randomPassword($length = 8)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);

    }

    public function cleanData($string)
    {
        $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\-]/', '',
            $string); // Removes special chars.lace('/[^\p{L}\p{N}\s]/u', '', $data);
    }

    public function convertTimeZone($userTime, $localTimeZone = 'Australia/Brisbane')
    {
        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $userTime, 'UTC')->setTimezone($localTimeZone);
        } catch (Exception $e) {
            return $userTime;
        }
    }
}
