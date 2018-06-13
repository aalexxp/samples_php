<?php

namespace App\Http\Controllers\Staff\CallCenter;

use App\Http\Controllers\Controller;
use App\Services\SimpleTemplateService;
use Illuminate\Contracts\Auth\Guard;

abstract class AbstractCallCenterController extends Controller
{

    protected $user = null;
    protected $destinationPath = "";

    public function __construct(Guard $auth, SimpleTemplateService $sts)
    {
        $this->user = $auth;
        $this->destinationPath = public_path() . "/upload/";
        parent::__construct($sts);
    }
}