<?php

namespace App\Observers;

use App\Events\ListingChanged;
use App\Models\Listing;

class ListingObserver extends AbstractObserver
{

    protected $notMonitoredChanges = [
        'created_at',
        'updated_at',
        'deleted_at',
        'listing_updated'
    ];

    protected static $listingsData = [];

    protected function updatingFunction(Listing $model, array $changes)
    {
        self::$listingsData[$model->id] = $changes;
    }

    protected function savedFunction(Listing $listing)
    {
        // Let's generate event if it's required
        event(new ListingChanged($listing,
            isset(self::$listingsData[$listing->id]) ? self::$listingsData[$listing->id] : []));
    }

}