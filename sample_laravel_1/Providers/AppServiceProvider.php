<?php

namespace App\Providers;

use App\Models\Comms\Signature;
use App\Models\Comms\Template;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactsProperties;
use App\Models\Listing;
use App\Models\Messages\MsgEmail;
use App\Models\Messages\MsgSms;
use App\Models\Property;
use App\Models\Task;
use App\Models\User;
use App\Models\Agent\PropertyView;
use App\Observers\CompanyObserver;
use App\Observers\ContactObserver;
use App\Observers\ListingObserver;
use App\Observers\MsgEmailObserver;
use App\Observers\MsgSmsObserver;
use App\Observers\PropertyObserver;
use App\Observers\SignatureObserver;
use App\Observers\TaskObserver;
use App\Observers\TemplateObserver;
use App\Observers\UserObserver;
use App\Observers\PropertyViewObserver;
use App\Observers\ContactPropertiesObserver;
use App\Services\EntityService;
use App\Services\Interfaces\SmsInterface;
use App\Services\TwilioSmsService;
use App\Services\Communication\PlivoSmsService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191); // MySQL fix - see https://laravel-news.com/laravel-5-4-key-too-long-error

        // Add observers for logging changes
        Property::observe(PropertyObserver::class);
        Company::observe(CompanyObserver::class);
        Contact::observe(ContactObserver::class);
        Task::observe(TaskObserver::class);
        User::observe(UserObserver::class);
        Signature::observe(SignatureObserver::class);
        Template::observe(TemplateObserver::class);
        PropertyView::observe(PropertyViewObserver::class);
        ContactsProperties::observe(ContactPropertiesObserver::class);
        Listing::observe(ListingObserver::class);
        MsgEmail::observe(MsgEmailObserver::class);
        MsgSms::observe(MsgSmsObserver::class);

        Relation::morphMap((new EntityService())->entityTypes());

        //$this->app->bind(SmsInterface::class, TwilioSmsService::class);
        $this->app->bind(SmsInterface::class, PlivoSmsService::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }
}
