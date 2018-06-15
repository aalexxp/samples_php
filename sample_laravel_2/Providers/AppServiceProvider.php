<?php

namespace App\Providers;

use App\Delegate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        \Schema::defaultStringLength(191);

        Validator::extend('image64', function ($attribute, $value, $parameters, $validator) {
            if (is_int($value) || is_null($value)) {
                return true;
            }
            if (empty($value['file'])) {
                return false;
            }
            $type = explode('/', explode(':', substr($value['file'], 0, strpos($value['file'], ';')))[1])[1];
            if (in_array($type, $parameters)) {
                return true;
            }

            return false;
        });

        Validator::replacer('image64', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':values', join(",", $parameters), $message);
        });

        Validator::extend('uic_user', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters)) {
                return false;
            }
            if (Delegate::where('uic', $value)->first()) {
                return true;
            }
            return false;
        });

        Validator::extend('enabled', function ($attribute, $value, $parameters, $validator) {
            $delegate = Delegate::where($attribute, $value)->first();
            if (!$delegate) {
                return false;
            }
            if ($delegate->enabled && $delegate->confirmed) {
                return true;
            }
            return $value == 'foo';
        });
    }


    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        if ($this->app->environment('local', 'testing')) {
            $this->app->register(DuskServiceProvider::class);
        }
    }
}
