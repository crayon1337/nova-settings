<?php

namespace OptimistDigital\NovaSettings;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OptimistDigital\NovaSettings\Http\Middleware\Authorize;
use OptimistDigital\NovaSettings\Http\Middleware\SettingsPathExists;
use OptimistDigital\NovaTranslationsLoader\LoadsNovaTranslations;
use Laravel\Nova\Events\ServingNova;

class NovaSettingsServiceProvider extends ServiceProvider
{
    use LoadsNovaTranslations;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'nova-settings');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslations(__DIR__ . '/../resources/lang', 'nova-settings', true);

        if ($this->app->runningInConsole()) {
            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'migrations');

            // Publish config
            $this->publishes([
                __DIR__ . '/../config/' => config_path(),
            ], 'config');
        }
        
        Nova::serving(ServingNova $event) {
            $locale = null;
            
            if(auth()->check()) 
                $locale = auth()->user()->id . '.locale';
            
            app()->setLocale($locale ?? app()->getLocale());
        });
    }

    public function register()
    {
        $this->registerRoutes();

        $this->mergeConfigFrom(
            __DIR__ . '/../config/nova-settings.php',
            'nova-settings'
        );
    }

    protected function registerRoutes()
    {
        if ($this->app->routesAreCached()) return;

        Route::middleware(['nova', Authorize::class, SettingsPathExists::class])
            ->group(__DIR__ . '/../routes/api.php');
    }
}
