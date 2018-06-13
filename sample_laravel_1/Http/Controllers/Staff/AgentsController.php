<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Auth\TokenController;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Property;
use App\Models\Contact;
use App\Models\ContactsProperties;
use App\Models\PropertyType;
use App\Services\AddressService;
use App\Services\Agent\ReportService;
use App\Services\AgentsApi;
use App\Services\AgentsComparatorService;
use App\Services\PropertyService;
use App\Services\RemoteAgentService;
use App\Services\RemoteAgentsService;
use App\Services\SuburbsApi;
use App\Services\GeocodingService;
use DB;
use Illuminate\Http\Request;
use App\Services\CustomFieldsService;

class AgentsController extends Controller
{

    const PAGE_TYPE = 'agent';
    const ELEMENTS_PER_PAGE = self::DEFAULT_ELEMENTS_PER_PAGE;

    protected $pageType = self::PAGE_TYPE;

    /**
     * Loading agents can be rather slow operation. That's why it should be used Ajax here.
     *
     * @param Request $request
     * @param GeocodingService $geocodingService
     * @param SuburbsApi $suburbsApi
     *
     * @param AddressService $addressService
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(
        Request $request,
        GeocodingService $geocodingService,
        SuburbsApi $suburbsApi,
        AddressService $addressService
    )
    {

        $currentSorting = (array)$suburbsApi->correctSorting();

        $property = null;
        if ($propertyId = $request->get('property')) {
            $property = Property::where('id', $propertyId)->first();
        }

        $agents = [];
        $suburbs = [];
        if ($property) {
            $property->load('agents');
            foreach ($property->agents AS $agent) {
                $agents[] = (int)$agent->id;
            }

            if (!$property->latitude || !$property->longitude) {
                $geocodingService->setPropertyCoordinates($property);
            }

            $suburbs = array_merge_recursive($suburbs, $addressService->suburbArrayForAddressSelector($property));
        }
        $referer = $request->header('referer');
        if (in_array(app('router')->getRoutes()->match(app('request')->create($referer))->getName(),
            ['lead.show', 'lead.create'])) {
            $from_lead = true;
        } else {
            $from_lead = false;
        }
        return view($request->get('spa') ? 'staff.agent.spa' : 'staff.agent.index', [
            'disableSearch' => true,
            'disableEditableTable' => true,
            'currentSorting' => $currentSorting,
            'from_lead' => $from_lead,
            'agents' => $agents,
            'suburbs' => $suburbs,
            'property' => $property
        ]);
    }

    public function processFilterParams(Request $request)
    {
        $period = (int)$request->get('period', 12);
        if ($period < 1) {
            $period = 1;
        }
        if ($period > 24) {
            $period = 24;
        }

        $suburbs = $request->get('suburbs');

        $property = null;
        $propertyId = $request->get('property_id');
        if ($propertyId) {
            $property = Property::where('id', $propertyId)->first();
            if ($property) {
                $property->load(['agents']);
            }
        }

        $distance = [];
        if ($property && $request->get('filter_radius')) {
            $distance = [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
                'radius' => $request->get('filter_radius')
            ];
        }

        // Let's try to parse prices
        $priceFrom = '';
        $priceTo = '';
        $prices = $request->get('prices', '');
        if ($prices) {
            $pricesParts = explode(',', $prices);
            if (count($pricesParts) === 2) {
                if (is_numeric($pricesParts[0])) {
                    $priceFrom = (int)$pricesParts[0] * 1000;
                }
                if (is_numeric($pricesParts[1])) {
                    $priceTo = (int)$pricesParts[1] * 1000;
                }
            }
        }

        return [$period, $suburbs, $property, $distance, $priceFrom, $priceTo];
    }

    public function loadTable(Request $request, SuburbsApi $suburbsApi, Contact $contact)
    {
        list($period, $suburbs, $property, $distance, $priceFrom, $priceTo) = $this->processFilterParams($request);

        //$currentSorting = $suburbsApi->correctSorting($request->get('sort_by'));
        $currentSorting = preg_split('/,/', $request->get('sort_by'), null, PREG_SPLIT_NO_EMPTY);

        $propertyCount = '';
        if ($property && $request->get('filter_property_count')) {
            $propertyCount = $request->get('filter_property_count');
        }

        $agents = [];
        if ($property AND count($property->agents) > 0) {
            $agents = $property->agents->keyBy('ronas_id');
        }

        $street = '';
        if ($property AND $property->street) {
            $street = $property->street;
        }

        if (null !== $request->get('property_type')) {
            $property_types = PropertyType::whereIn('text_id', $request->get('property_type'))
                ->orWhereIn('parent_id', function ($query) use ($request) {
                    $query->select('id')
                        ->from(with(new PropertyType)->getTable())
                        ->whereIn('text_id', $request->get('property_type'));
                })
                ->pluck('text_id')->toArray();
        } else {
            $property_types = [];
        }


        $data = $suburbsApi->searchAgents([
            'suburbs' => $suburbs,
            'agents' => $request->get('filter_agent'),
            'agency' => $request->get('filter_agency'),
            'beds' => $request->get('beds'),
            'propertyType' => $property_types,
            'averagePriceMin' => $priceFrom,
            'averagePriceMax' => $priceTo,
            'sortBy' => $currentSorting,
            'period' => $period,
            'search' => '',
            'any_source' => 1,
            'distance' => $distance ? $distance : '',
            'propertyCount' => $propertyCount,
            'page' => $request->get('page', 1),
            'property_street' => $street,
            'agent_statuses' => $request->get('agent_status', [])
        ]);

        $added_agent_companies = $added_agent_id = [];
        if ($property) {
            $added_agent_id = array_map('trim', $property->agents->pluck('ronas_id')->toArray());
            foreach ($property->agents as $agent) {
                $added_agent_companies[] = $agent->getCompanyName();
            }
        }


        $agent_Ids = [];

        if ($data) {
            foreach ($data->data AS $agent) {
                $agent_Ids[] = $agent->id;

                $agent->from_top_company = false;
                $agent->is_added_agent = false;
                foreach ($added_agent_companies as $item) {
                    if (\in_array($agent->id, $added_agent_id)) {
                        $agent->is_added_agent = true;
                        break;
                    } else {
                        if (preg_match("/" . preg_quote($item, '/') . "/", $agent->agency)) {
                            $agent->from_top_company = true;
                            break;
                        }
                    }


                }
            }
        }

        if (!empty($agent_Ids)) {
            $agentPropertiesQuery = $contact->select('contacts.id', 'contacts.ronas_id', 'contacts.status',
                'contacts.refer_contact_id', 'contacts.declined_count', 'contacts.selective_conditions',
                DB::raw('count(contacts_properties.id) AS properties'),
                DB::raw('count(properties.id) AS sold_properties'))
                ->leftJoin('contacts_properties', 'contacts_properties.contact_id', '=',
                    'contacts.id')
                ->leftJoin('properties', function ($join) {
                    $join->on('properties.id', '=', 'contacts_properties.property_id')
                        ->whereNotNull('contacts_properties.sold_at');

                })
                ->whereIn('contacts.ronas_id', $agent_Ids);
            $agentProperties = $agentPropertiesQuery->groupBy('contacts.id')->get()->keyBy('ronas_id');
            foreach ($data->data AS $index => &$agent) {
                if (isset($agentProperties[$agent->id])) {
                    $agent->properties_count = $agentProperties[$agent->id]->properties;
                    $agent->sold_properties = $agentProperties[$agent->id]->sold_properties;
                    $agent->icon_html = $agentProperties[$agent->id]->getStatusIcon();
                    $agent->paid_fees_count = count($agentProperties[$agent->id]->paid_fees);
                }
            }
            unset($agent);
        }

        if (isset($data->data)) {
            array_multisort(array_column($data->data, 'filtered_sold_lead_count'), SORT_DESC,
                array_column($data->data, 'filtered_all_sold_count'), SORT_DESC,
                $data->data);
        }

        $spa = $request->get('spa');

        return view('staff.agent.table',
            compact('data', 'currentSorting', 'property', 'agents', 'spa', 'added_agent_companies'));
    }

    public function bestAgent(Request $request, SuburbsApi $suburbsApi, Contact $contact, RemoteAgentService $service)
    {
        $propertyId = $request->get('property_id');

        $currentAgencies = [];

        if ($propertyId) {
            $property = Property::where('id', $propertyId)->first();
            if ($property) {
                $property->load(['agents', 'agents.company']);

                foreach ($property->agents AS $agent) {
                    $currentAgencies[] = (int)$agent->company->ronas_id;
                }
            } else {
                return $this->jsonError('Property not found!');
            }
        } else {
            return $this->jsonError('Property not found!');
        }


        $period = (int)$request->get('period', 12);
        if ($period < 1) {
            $period = 1;
        }
        if ($period > 24) {
            $period = 24;
        }

        $suburbs = $request->get('suburbs');

        $currentSorting = $suburbsApi->correctSorting($request->get('sort_by'));

        $distance = [];
        if ($request->get('filter_radius')) {
            $distance = [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
                'radius' => $request->get('filter_radius')
            ];
        }

        $propertyCount = '';
        if ($request->get('filter_property_count')) {
            $propertyCount = $request->get('filter_property_count');
        }

        $agents = [];
        if (\count($property->agents) > 0) {
            $agents = array_keys($property->agents->keyBy('ronas_id')->toArray());
        }

        $declinedAgents = [];
        if ($request->get('declined_agents')) {
            $declinedAgents = $request->get('declined_agents');
        }

        // Let's try to parse prices
        $priceFrom = '';
        $priceTo = '';
        $prices = $request->get('prices', '');
        if ($prices) {
            $pricesParts = explode(',', $prices);
            if (\count($pricesParts) === 2) {
                if (is_numeric($pricesParts[0])) {
                    $priceFrom = (int)$pricesParts[0] * 1000;
                }
                if (is_numeric($pricesParts[1])) {
                    $priceTo = (int)$pricesParts[1] * 1000;
                }
            }
        }

        $agent = $suburbsApi->bestAgent([
            'suburbs' => $suburbs,
            'agents' => $request->get('filter_agent'),
            'agency' => $request->get('filter_agency'),
            'propertyType' => $request->get('property_type'),
            'beds' => $request->get('beds'),
            'averagePriceMin' => $priceFrom,
            'averagePriceMax' => $priceTo,
            'sortBy' => $currentSorting,
            'period' => $period,
            'search' => '',
            'any_source' => 1,
            'distance' => $distance ? $distance : '',
            'propertyCount' => $propertyCount,
            'page' => $request->get('page', 1),
            'currentAgencies' => $currentAgencies,
            'currentAgents' => $agents,
            'declinedAgents' => $declinedAgents
        ]);

        $acs = new AgentsComparatorService($property);

        if (!isset($agent->id)) {
            return $this->jsonError('User not found');
        }

        $localAgent = $service->getLocalAgentByRonasId($agent->id);

        if ($localAgent) {
            $tags = $localAgent->tags()->declined()->get()->toArray();
            if ($tags) {
                $declinedAgents[] = $agent->id;
                return $this->jsonOk('declined', $declinedAgents);
            }

            $property->agents()->attach($localAgent->id,
                [
                    'priority' => count($property->agents) + 1,
                    'agency_id' => $localAgent->company_id,
                    'properties_filter' => $this->getFilter($request)
                ]
            );
        }

        $acs->save($property);

        if (isset($agent->name)) {
            return $this->jsonOk($agent->name);
        }

        return $this->jsonError();
    }

    public function loadSuburbs(Request $request, SuburbsApi $suburbsApi)
    {
        $params = $request->all();
        $params['limit'] = 7;

        $suburbs = $suburbsApi->getSuburbs($params);
        $result = [];
        if (is_array($suburbs)) {
            foreach ($suburbs AS $singleSuburb) {
                $result[] = [
                    'name' => $singleSuburb->suburb . ' ' . $singleSuburb->state . ' ' . $singleSuburb->postcode,
                    'id' => $singleSuburb->suburb . ',' . $singleSuburb->postcode
                ];
            }
        }

        return response()->json($result);
    }

    public function loadAgents(Request $request, AgentsApi $agentsApi)
    {
        $params = $request->all();
        $params['limit'] = 7;


        $agents = $agentsApi->getAgentsList($params);
        $exists_ronas_id = Contact::whereIn('ronas_id', array_column($agents, 'id'))->get()->pluck('ronas_id')->toArray();
        $result = [];

        if (\is_array($agents)) {
            foreach ($agents AS $agent) {
                if (in_array($agent->id, $exists_ronas_id)) {
                    $result[] = [
                        'name' => $agent->name . ' (' . $agent->agency_name . ')',
                        'id' => $agent->id
                    ];
                }
            }
        }

        return response()->json($result);
    }

    public function loadAgencies(Request $request, AgentsApi $agentsApi)
    {
        $params = $request->all();
        $params['limit'] = 7;

        $agencies = $agentsApi->getAgenciesList($params);
        $result = [];

        if (\is_array($agencies)) {
            foreach ($agencies AS $agency) {
                $result[] = [
                    'name' => $agency->name,
                    'id' => $agency->id
                ];
            }
        }

        return response()->json($result);
    }

    public function show($id, CustomFieldsService $cf)
    {
        $custom_fields = [];
        $suburbsApi = new SuburbsApi();
        /*
         * Get ronas agent details
         */
        $agent = $suburbsApi->getAgent($id);

        foreach ($agent->listings as $key => $part) {
            $sort[$key] = strtotime($part->date_sold);
        }
        array_multisort($sort, SORT_DESC, $agent->listings);

        foreach ($agent->listings as &$entity) {
            $entity->price_string = '';

            if (isset($entity->initial_price) AND $entity->initial_price->price_string !== 'Contact Agent') {
                $entity->price_string = $entity->initial_price->price_string;
            }
        }
        unset($entity);

        $specials = ['second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth'];
        $agentEmails = [];
        $agentPhones = [];

        if (!empty($agent->email)) {
            $objEmail = new \stdClass();
            $objEmail->email = $agent->email;
            $agentEmails[] = $objEmail;
        }

        if (!empty($agent->phone_number)) {
            $objPhone = new \stdClass();
            $objPhone->phone = $agent->phone_number;
            $agentPhones[] = $objPhone;
        }
        foreach ($specials AS $special) {
            $keyEmail = 'email_' . $special;
            $keyPhone = 'phone_number_' . $special;
            if (isset($agent->$keyEmail) && !empty($agent->$keyEmail)) {
                $objEmail = new \stdClass();
                $objEmail->email = $agent->$keyEmail;
                $agentEmails[] = $objEmail;
            }
            if (isset($agent->$keyPhone) && !empty($agent->$keyPhone)) {
                $objPhone = new \stdClass();
                $objPhone->phone = $agent->$keyPhone;
                $agentPhones[] = $objPhone;
            }
        }
        $agent->emails = $agentEmails;
        $agent->phones = $agentPhones;
        /*
         * Check if ronas agent is linked to contacts or not if, yes then combine the details
         * from contacts and ronas data
         */
        $contact = Contact::where('ronas_id', $id)->where('type', Contact::TYPE_AGENT)->whereNull('dest_contact_id')->first();
        if ($contact) {
            return redirect(route('contact.show', $contact->id));
        }
        $agent->agent_ronas_id = $id;

        return view('staff.agent.show', compact('agent', 'custom_fields'));
    }

    public function expandedData($agent_id, Request $request)
    {
        $suburbsApi = new SuburbsApi();
        list($period, $suburbs, $property, $distance, $priceFrom, $priceTo) = $this->processFilterParams($request);
        if (null !== $request->get('property_type')) {
            $property_types = PropertyType::whereIn('text_id', $request->get('property_type'))
                ->orWhereIn('parent_id', function ($query) use ($request) {
                    $query->select('id')
                        ->from(with(new PropertyType)->getTable())
                        ->whereIn('text_id', $request->get('property_type'));
                })
                ->pluck('text_id')->toArray();
        } else {
            $property_types = [];
        }

        $filterParameters = [
            'suburbs' => $suburbs,
            'property_type' => $property_types,
            'averagePriceMin' => $priceFrom,
            'averagePriceMax' => $priceTo,
            'any_source' => 1,
            'period' => $period,
            'distance' => $distance ? $distance : '',
            'beds' => $request->get('beds', '')
        ];
        $entities = $suburbsApi->getFilteredListings($agent_id, 'date_sold', [
            'query' => $filterParameters
        ]);
        foreach ($entities as &$entity) {
            $entity->price_string = '';

            if (isset($entity->initial_price)) {
                $entity->price_string = $entity->initial_price->price_string;
            }
        }
        unset($entity);
        $property = Property::findorNew($request->get('property_id', -1));

        $property_street = preg_replace('/[^\sA-Za-z]/', '', $property->street);
        return view('staff.agent.expanded', compact('entities', 'agent_id', 'property', 'property_street'));
    }

    public function addToProperty(Request $request, RemoteAgentService $service)
    {
        $ronasId = (int)$request->get('agent');
        if (!$ronasId) {
            return $this->jsonError('Empty RonasID');
        }

        /** @var Property $property */
        $property = Property::whereId($request->get('property', -1))->first();
        $acs = new AgentsComparatorService($property);
        if (!$property) {
            return $this->jsonError('Property not found!');
        }
        $agent = $service->getLocalAgentByRonasId($ronasId);

        $property->load('agents');
        $save_data = ['priority' => count($property->agents) + 1];

        $save_data['properties_filter'] = $this->getFilter($request);
        $save_data['agency_id'] = $agent->company_id;

        $property->agents()->attach($agent->id, $save_data);
        $acs->save($property);

        return $this->jsonOk('Agent successfully added');
    }

    protected function getFilter(Request $request)
    {
        $query = $request->all();
        $saveData = [];
        foreach ([
                     'filter_radius',
                     'period',
                     'prices',
                     'property',
                     'property_id',
                     'property_type',
                     'suburbs',
                     'beds',
                     'agent_status'
                 ] AS $field) {
            if (isset($query[$field])) {
                $saveData[$field] = $query[$field];
            }
        }

        return json_encode($saveData);
    }

    public function removeFromProperty(Request $request, RemoteAgentService $service, PropertyService $pService)
    {
        $agent_id = (int)$request->get('agent_id', -1);
        if ($agent_id > 0) {
            $agent = Contact::findOrFail($agent_id);
        } else {
            $ronasId = (int)$request->get('agent');
            if (!$ronasId) {
                return $this->jsonError("Empty RonasID");
            }
            $agent = $service->getLocalAgentByRonasId($ronasId);
        }

        /** @var Property $property */
        $property = Property::whereId($request->get('property', -1))->first();
        if (!$property) {
            return $this->jsonError('Property not found!');
        }

        $pService->removeAgent($property, $agent);

        return $this->jsonOk('Agent successfully removed');
    }

    public function getAgentAdditionalData($contact, $agentEmails, $agentPhones)
    {

        if ($contact) {
            $contact->load(['company', 'phones', 'emails', 'creator', 'tags']);
            $contactEmails = [];
            $allEmails = $contact->emails;
            foreach ($contact->emails AS $emailC) {
                $contactEmails[] = $emailC->email;
            }
            foreach ($agentEmails AS $index => $emailA) {
                if (!in_array($emailA->email, $contactEmails)) {
                    $objEmail = new \stdClass();
                    $objEmail->email = $emailA->email;
                    $contactEmails[] = $emailA->email;
                    $allEmails[] = $objEmail;
                }
            }
            if (!empty ($allEmails)) {
                $contact->emails = $allEmails;
            }

            $contactPhones = [];
            $allPhones = $contact->phones;
            foreach ($contact->phones AS $phoneC) {
                $contactPhones[] = $phoneC->phone;
            }
            foreach ($agentPhones AS $index => $phoneA) {
                if (!in_array($phoneA->phone, $contactPhones)) {
                    $objPhone = new \stdClass();
                    $objPhone->phone = $phoneA->phone;
                    $contactPhones[] = $phoneA->phone;
                    $allPhones[] = $objPhone;
                }
            }
            if (!empty ($allPhones)) {
                $contact->phones = $allPhones;
            }
        }

        return $contact;
    }

    public function sendReportsTest($email, ReportService $report)
    {
        $sentCount = $report->sendEmails($email);

        if ($sentCount > 0) {
            if ($sentCount === 1) {
                return "One report was sent to email {$email}";
            } else {
                return "$sentCount reports were sent to email {$email}";
            }
        }

        return 'We don\'t have data for reports';
    }
}