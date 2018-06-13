<?php

namespace App\Http\Controllers\Staff\CallCenter;

use App\Models\CallCenter\Account;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Hash;
use Plivo\Resources\ResourceList;
use Plivo\RestClient;
use DB;

class AccountController extends AbstractCallCenterController
{
    const PAGE_TYPE = 'account';
    const ELEMENTS_PER_PAGE = self::DEFAULT_ELEMENTS_PER_PAGE;
    protected $methodType = 'index';
    protected $pageType = self::PAGE_TYPE;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $methodType = 'changepassword';
        $pageType = $this->pageType;
        $account = new Account();
        return view('staff.call-center.changepassword.index', compact('account', 'pageType', 'methodType'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Account $account)
    {
        //echo $this->user->user()->id;exit;
        $userResult = Account::find($this->user->user()->id);
        $newpassword = $request['newpassword'];

        if (Hash::check($request['oldpassword'], $userResult->password)) {
            $userResult->password = Hash::make($newpassword);
            $userResult->save();
            return $this->flashSuccess('Password Change successfully!', route('account.index'));
        } else {
            return $this->flashError('Old password does not match! Try again', route('account.index'));
        }


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CallCenter\Account $account
     * @return \Illuminate\Http\Response
     */
    public function edit(Account $account)
    {
        $methodType = 'setup';
        $pageType = $this->pageType;
        $userResult = Account::find($this->user->user()->id);

        return view('staff.call-center.plivosetup.edit', compact('userResult', 'account', 'pageType', 'methodType'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\CallCenter\Account $account
     * @return \Illuminate\Http\Response
     * @throws \Plivo\PlivoError
     */
    public function update(Request $request, Account $account)
    {
        $userResult = Account::find($this->user->user()->id);
        $userResult->auth_id = $request->aid;
        $userResult->auth_token = $request->token;

        $p = new RestClient($request->aid, $request->token);


        $responses = $p->getPhoneNumbers()->list('US', [
            'type' => 'local',
            # The type of number you are looking for. The possible number types are local, national and tollfree.
            'pattern' => '210',
            # Represents the pattern of the number to be searched.
            'region' => 'Texas'
            # This filter is only applicable when the number_type is local. Region based filtering can be performed.
        ]);

        if ($responses instanceof ResourceList) {
            $userResult->save();
            return $this->flashSuccess('Plivo account setup successfully!', route('account.edit'));

        } else {
            return $this->flashError('Invalid credential please check again!', route('account.edit'));
        }

    }
}
