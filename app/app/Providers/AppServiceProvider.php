<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (str_starts_with($view->name(),'errors.')) return;
            $business = \Illuminate\Support\Facades\DB::table('business_settings')->first();
            $view->with('business', $business ?? (object) ['name'=>'Forma Construction','currency'=>'PHP','logo_path'=>null,'accent_color'=>'#d9f36c','email'=>'','phone'=>'','address'=>'']);
        });
    }
}
