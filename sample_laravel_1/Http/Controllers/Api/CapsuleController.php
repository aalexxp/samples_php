<?php

namespace App\Http\Controllers\Api;

use App\Models\Property;
use App\Services\Api\CapsuleService;
use App\Services\Api\LogService;
use App\Services\SimpleTemplateService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CapsuleController extends Controller
{

    protected $log;

    public function __construct(LogService $log, SimpleTemplateService $sts)
    {
        parent::__construct($sts);
        $this->log = $log;
    }

    public function createParty(Request $request, CapsuleService $cs)
    {
        $params = $request->all();
        $this->log->setMainParameters();
        $this->log->request($params);
        if ( ! isset($params['type'])) {
            throw new \Exception('Parameter "type" is required!');
        }
        if ( ! empty($params['party'])) {
            $params = $params['party'];
        }
        switch (mb_strtolower($params['type'])) {
            // We should create the person with property
            case 'person':
                return $this->log->success($cs->postPersonWithProperty($params));
        }
        $this->log->fail('Incorrect "type" value');

        return null;
    }

    // Add task to Property
    public function createTask(Request $request, CapsuleService $cs)
    {
        // By default we should create a task for property
        $params = $request->all();
        $this->log->setMainParameters();
        $this->log->request($params);
        if ( ! empty($params['task'])) {
            $params = $params['task'];
        }
        if (empty($params['party']['id']) or empty($params['dueOn'])) {
            $this->log->fail('Parameters "Party Id" and Due On  are required!');
            throw new \Exception('Parameters "Party Id" and Due On  are required!');
        }

        return $this->log->success($cs->postTask($params));
    }

    // Add note to property
    public function createEntries(Request $request, CapsuleService $cs)
    {
        // By default we should add a note to property
        $params = $request->all();
        $this->log->setMainParameters();
        $this->log->request($params);
        if ( ! empty($params['entry'])) {
            $params = $params['entry'];
        }
        if (empty($params['type']) or $params['type'] !== 'note') {
            $this->log->fail('Parameters "type" must have "note" value!');
            throw new \Exception('Parameters "type" must have "note" value!');
        }

        return $this->log->success($cs->postNote($params));
    }

    // Fix existed property
    public function fixProperty($id, Request $request, CapsuleService $cs)
    {
        $params = $request->all();

        $this->log->setMainParameters();
        $this->log->request($params);

        /** @var Property $property */
        $property = Property::findOrFail($id);

        $this->log->step('Property [ID=' . $property->id . '] was found');

        if (isset($params['party'])) {
            $params = $params['party'];
        }

        return $this->log->success($cs->postAdditionalDataToProperty($params, $property));
    }

}
