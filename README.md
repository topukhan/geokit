# Geokit - Laravel Geocoding Toolkit

A clean and extensible geocoding toolkit for Laravel that supports multiple providers with automatic fallback handling.

## Features

- 🌍 **Multiple Providers**: Geoapify (premium) and Nominatim (free) support
- 🔄 **Smart Fallback**: Automatically falls back to next provider if one fails
- 🛡️ **Quota Protection**: Detects API quota issues and handles them gracefully  
- 🎯 **Consistent Results**: Unified response format across all providers
- ⚡ **Laravel Integration**: Facade and Service injection support
- 🔧 **Configurable**: Easy configuration and extensible architecture

## Installation

Install the package via Composer:

```bash
composer require topukhan/geokit
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=geokit-config
```

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# Optional: Your Geoapify API key (if you have one)
GEOKIT_GEOAPIFY_KEY=your_geoapify_api_key_here

# Optional: Request timeout in seconds (default: 30)
GEOKIT_TIMEOUT=30

# Optional: Maximum results per search (default: 10)
GEOKIT_MAX_RESULTS=10

# Optional: User agent for API requests
GEOKIT_USER_AGENT="Your App Name/1.0"
```

### Config File

The `config/geokit.php` file allows you to customize:

- Provider order and selection
- API keys
- Request timeouts
- Result limits

## Usage

### Using the Facade

```php
use Topukhan\Geokit\Facades\Geokit;

$response = Geokit::search('Tongi, Dhaka');

// Check if we got results
if ($response->hasResults()) {
    echo "Found {$response->count()} results\n";
    
    // Get the first result
    $first = $response->first();
    echo "Best match: {$first->formatted}\n";
    echo "Coordinates: {$first->lat}, {$first->lng}\n";
    echo "Provider: {$first->provider}\n";
    
    // Access address components
    if (isset($first->components['city'])) {
        echo "City: {$first->components['city']}\n";
    }
}

// Check if fallback was used
if ($response->usedFallback) {
    echo "Used fallback providers\n";
}

// See which providers failed
if (!empty($response->failedProviders)) {
    echo "Failed providers: " . implode(', ', $response->failedProviders) . "\n";
}
```

### Using Service Injection

```php
use Topukhan\Geokit\Services\AddressResolverService;

class LocationController extends Controller
{
    public function search(Request $request, AddressResolverService $geokit)
    {
        $response = $geokit->search($request->input('query'));
        
        return response()->json($response->toArray());
    }
}
```

## Response Format

All searches return a `GeocodeResponse` object with this structure:

```php
GeocodeResponse {
    +query: string           // Original search query
    +results: array         // Array of GeocodeResult objects
    +usedFallback: bool     // Whether fallback providers were used
    +failedProviders: array // Names of providers that failed
}
```

Each result in the `results` array is a `GeocodeResult` object:

```php
GeocodeResult {
    +provider: string    // Provider name (e.g., 'geoapify', 'nominatim')
    +formatted: string   // Full formatted address
    +lat: float         // Latitude
    +lng: float         // Longitude
    +components: array  // Address components (city, state, country, etc.)
}
```

### Example Response

```json
{
    "query": "Tongi, Dhaka",
    "results": [
        {
            "provider": "geoapify",
            "formatted": "Tongi, Gazipur District, Dhaka Division, Bangladesh",
            "lat": 23.8896,
            "lng": 90.3961,
            "components": {
                "city": "Tongi",
                "district": "Gazipur District", 
                "state": "Dhaka Division",
                "country": "Bangladesh",
                "country_code": "BD"
            }
        }
    ],
    "usedFallback": false,
    "failedProviders": []
}
```

## Provider Details

### Geoapify
- **Type**: Premium (requires API key)
- **Quota**: Varies by plan
- **Accuracy**: High
- **Coverage**: Global

### Nominatim  
- **Type**: Free (no API key required)
- **Quota**: Rate limited
- **Accuracy**: Good
- **Coverage**: Global (OpenStreetMap data)

## Error Handling

The package handles various error scenarios automatically:

- **Invalid API Keys**: Automatically falls back to next provider
- **Quota Exceeded**: Detects quota issues and skips provider
- **Network Timeouts**: Respects configured timeout limits
- **Rate Limits**: Handles rate limiting gracefully

## Extending with New Providers

To add a new geocoding provider:

1. Create a new class implementing `GeocodingDriverInterface`
2. Add it to the service provider's provider mapping
3. Update the configuration file

Example:

```php
use Topukhan\Geokit\Contracts\GeocodingDriverInterface;

class GoogleGeocoder implements GeocodingDriverInterface
{
    public function getName(): string
    {
        return 'google';
    }
    
    // Implement other interface methods...
}
```

## Requirements

- PHP 8.1+
- Laravel 10.0+

## License

This package is open-sourced software licensed under the MIT license.