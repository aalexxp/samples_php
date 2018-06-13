<?php
/**
 * Created by PhpStorm.
 * User: andrej
 * Date: 20.11.2017
 * Time: 19:47
 */

namespace App\Listeners;

use App\Events\ListingChanged;
use App\Events\PropertyChanged;
use App\Models\Contact;
use App\Models\Listing;
use App\Models\ContactsProperties;
use App\Models\Tracks\TrackTask;
use App\Services\ListingService;
use App\Services\RemoteAgentService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;

class PropertySubscriber implements ShouldQueue
{
    public $listingService;

    public function __construct(ListingService $listingService)
    {
        $this->listingService = $listingService;
    }

    public function handle(PropertyChanged $propertyChanged)
    {
        $changes = $propertyChanged->getChanges();
        if (isset($changes['listing_id']) && null === $changes['listing_id']['old'] && $changes['listing_id']['new'] > 0) {
            $listing = $propertyChanged->getListing();
            if(null!==$listing){
                $this->listingService->changeListingStatus($listing, $listing->status);
            }
        }
    }
}