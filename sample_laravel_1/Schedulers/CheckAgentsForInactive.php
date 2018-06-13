<?php

namespace App\Schedulers;

use App\Events\MilestoneNotChanged;
use App\Models\Contact;
use App\Models\Property;
use App\Models\Workflow\Trigger;
use App\Services\ContactService;
use App\Services\SuburbsApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class CheckAgentsForInactive extends Command
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check_agents_for_inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Agents In scraper for 4 months inactive';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ContactService $contactService, SuburbsApi $suburbsApi)
    {
        $contacts = Contact::where(['type' => Contact::TYPE_AGENT])
            ->where('status', '<>', Contact::STATUS_INACTIVE)
            ->where('ronas_id', '>', 0)
            ->where('last_activity_at', '<', DB::raw('NOW()-INTERVAL 4 MONTH'))
            ->limit(config('settings.agents_to_check_inactive'))
            ->get();
        /** @var Contact $contact */
        foreach ($contacts as $contact) {
            if ($contact->ronas_id) {
                $entities = $suburbsApi->getFilteredListingsByContact($contact, 'date_sold', [
                    'query' => [
                        'all_channels' => 1,
                        'any_source' => 1,
                    ]
                ]);
                $contactService->SetLastActivity($contact, $entities);
            }
        }
    }

}