<?php
/**
 * Created by PhpStorm.
 * User: andrej
 * Date: 20.11.2017
 * Time: 19:47
 */

namespace App\Listeners;

use App\Events\ListingChanged;
use App\Models\Contact;
use App\Models\Listing;
use App\Models\ContactsProperties;
use App\Models\Tracks\TrackTask;
use App\Services\ListingService;
use App\Services\RemoteAgentService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;

class ListingSubscriber implements ShouldQueue
{
    public $remoteAgentService;
    public $listingService;

    public function __construct(RemoteAgentService $service, ListingService $listingService)
    {
        $this->remoteAgentService = $service;
        $this->listingService = $listingService;
    }

    public function handle(ListingChanged $listingChanged)
    {
        $changes = $listingChanged->getChanges();
        $listing = $listingChanged->getListing();
        if (isset($changes['status']['new'])) {
            $this->listingService->changeListingStatus($listing, $changes['status']['new']);
        }
    }
}