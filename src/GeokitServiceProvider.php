<?php

namespace Topukhan\Geokit;

use Illuminate\Support\ServiceProvider;
use Topukhan\Geokit\Services\AddressResolverService;
use Topukhan\Geokit\Services\Geocoders\GeoapifyGeocoder;
use Topukhan\Geokit\Services\Geocoders\NominatimGeocoder;

class GeokitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/geokit.php',
            'geokit'
        );

        // Register geocoder services
        $this->app->singleton(GeoapifyGeocoder::class, function ($app) {
            return new GeoapifyGeocoder(
                $app['config']->get('geokit.api_keys.geoapify'),
                $app['config']->get('geokit.timeout', 30)
            );
        });

        $this->app->singleton(NominatimGeocoder::class, function ($app) {
            return new NominatimGeocoder(
                $app['config']->get('geokit.timeout', 30)
            );
        });

        // Register main resolver service
        $this->app->singleton(AddressResolverService::class, function ($app) {
            $providers = [];
            $providerNames = $app['config']->get('geokit.default_providers', ['geoapify', 'nominatim']);

            foreach ($providerNames as $providerName) {
                $providers[] = match ($providerName) {
                    'geoapify' => $app->make(GeoapifyGeocoder::class),
                    'nominatim' => $app->make(NominatimGeocoder::class),
                    default => null,
                };
            }

            return new AddressResolverService(array_filter($providers));
        });

        // Register facade binding
        $this->app->alias(AddressResolverService::class, 'geokit');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/geokit.php' => config_path('geokit.php'),
            ], 'geokit-config');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            AddressResolverService::class,
            GeoapifyGeocoder::class,
            NominatimGeocoder::class,
            'geokit',
        ];
    }
}