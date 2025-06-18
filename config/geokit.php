<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Geocoding Providers
    |--------------------------------------------------------------------------
    |
    | This array contains the geocoding providers that will be used by default.
    | The providers will be tried in the order they are listed. If one fails,
    | the next one will be attempted.
    |
    | Available providers: 'geoapify', 'nominatim', 'locationiq'
    |
    */
    'default_providers' => [
        'geoapify',
        'locationiq',
        'nominatim',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    |
    | Configure your geocoding service API keys here. If you don't provide
    | a key for a service that requires one, the package will attempt to
    | use a default key, but this may have limited quota.
    |
    */
    'api_keys' => [
        'geoapify' => env('GEOKIT_GEOAPIFY_KEY', 'f763735f7266423ba51899a18e34efcb'),
        'locationiq'=> env('GEOKIT_LOCATIONIQ_KEY','pk.129943496f1af64462174442090bb50a'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum time (in seconds) to wait for a response from geocoding
    | providers before timing out.
    |
    */
    'timeout' => env('GEOKIT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Maximum Results
    |--------------------------------------------------------------------------
    |
    | The maximum number of results to return from each geocoding provider.
    | This helps control response size and processing time.
    |
    */
    'max_results' => env('GEOKIT_MAX_RESULTS', 10),

    /*
    |--------------------------------------------------------------------------
    | User Agent
    |--------------------------------------------------------------------------
    |
    | The User-Agent string to send with API requests. Some providers
    | (like Nominatim) require a valid User-Agent header.
    |
    */
    'user_agent' => env('GEOKIT_USER_AGENT', 'Geokit Laravel Package/1.0'),
];
