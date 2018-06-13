<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Services\AgentsApi;
use Illuminate\Http\Request;

class MergeController extends Controller {

    public function mergeContactRemote($id) {
        $contact = Contact::findOrFail($id);
        $contact->load(['company','emails','phones']);

        return view('staff.modal.forms.remote-merge-form', compact('contact'));
    }

    public function loadAgents(Request $request, AgentsApi $agentsApi)
    {
        $params = $request->all();
        $params['limit'] = 10;
        $params['full_data'] = 1;

        $agents = $agentsApi->getAgentsList($params);

        return view('staff.modal.forms.merge.agents', compact('agents'));
    }

    public function updateContact($id, Request $request) {
        /** @var Contact $contact */
        $contact = Contact::findOrFail($id);
        $contact->ronas_id = $request->get('remote_obj');
        $contact->ronas_person_id = $request->get('person');
        $contact->save();

        if ($url = $request->get('back')) {
            return $this->flashSuccess("[{$contact->name}] was successfully updated!", $url);
        }

        return $this->flashSuccess("[{$contact->name}] was successfully updated!", route('contact.index'));
    }

    public function mergeCompanyRemote($id) {
        $company = Company::findOrFail($id);

        return view('staff.modal.forms.remote-merge-form-company', compact('company'));
    }

    public function loadAgencies(Request $request, AgentsApi $agentsApi)
    {
        $params = $request->all();
        $params['limit'] = 10;
        $params['full_data'] = 1;

        $agencies = $agentsApi->getAgenciesList($params);

        return view('staff.modal.forms.merge.agencies', compact('agencies'));
    }

    public function updateCompany($id, Request $request) {
        /** @var Company $company */
        $company = Company::findOrFail($id);
        $company->ronas_id = $request->get('remote_obj');
        $company->save();

        if ($url = $request->get('back')) {
            return $this->flashSuccess("Company [{$company->name}] was successfully updated!", $url);
        }

        return $this->flashSuccess("Company [{$company->name}] was successfully updated!", route('company.index'));
    }

}