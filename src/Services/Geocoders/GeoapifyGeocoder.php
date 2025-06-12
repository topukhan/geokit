<?php

namespace Topukhan\Geokit\Services\Geocoders;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Topukhan\Geokit\Contracts\GeocodingDriverInterface;
use Topukhan\Geokit\Data\GeocodeResult;

class GeoapifyGeocoder implements GeocodingDriverInterface
{
    private bool $isAvailable = true;
    private const BASE_URL = 'https://api.geoapify.com/v1/geocode/search';

    public function __construct(
        private ?string $apiKey,
        private int $timeout = 30
    ) {
        // If no API key provided, mark as unavailable
        if (empty($this->apiKey)) {
            $this->isAvailable = false;
        }
    }

    public function getName(): string
    {
        return 'geoapify';
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable && !empty($this->apiKey);
    }

    public function markUnavailable(): void
    {
        $this->isAvailable = false;
    }

    public function search(string $query, int $maxResults = 10): array
    {
        if (!$this->isAvailable()) {
            throw new \Exception('Geoapify provider is not available');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->get(self::BASE_URL, [
                    'text' => $query,
                    'apiKey' => $this->apiKey,
                    'limit' => min($maxResults, 20), // Geoapify max is 20
                    'format' => 'json'
                ]);

            $this->handleErrorResponse($response);

            $data = $response->json();
            
            if (!isset($data['features']) || !is_array($data['features'])) {
                return [];
            }

            return $this->transformResults($data['features']);

        } catch (\Exception $e) {
            // If it's a quota/auth error, mark as unavailable
            if ($this->isQuotaOrAuthError($e)) {
                $this->markUnavailable();
            }
            
            throw $e;
        }
    }

    /**
     * Handle error responses from the API.
     */
    private function handleErrorResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $statusCode = $response->status();
        $body = $response->body();

        // Check for quota exceeded or invalid API key
        if (in_array($statusCode, [401, 403, 429])) {
            $this->markUnavailable();
            throw new \Exception("Geoapify API error (HTTP {$statusCode}): Authentication or quota issue");
        }

        // Check response body for specific error messages
        if (stripos($body, 'quota') !== false || stripos($body, 'limit') !== false) {
            $this->markUnavailable();
            throw new \Exception("Geoapify API quota exceeded");
        }

        if (stripos($body, 'invalid') !== false && stripos($body, 'key') !== false) {
            $this->markUnavailable();
            throw new \Exception("Geoapify API key is invalid");
        }

        throw new \Exception("Geoapify API error (HTTP {$statusCode}): {$body}");
    }

    /**
     * Check if the exception indicates a quota or authentication error.
     */
    private function isQuotaOrAuthError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        return str_contains($message, 'quota') ||
               str_contains($message, 'limit') ||
               str_contains($message, 'invalid') ||
               str_contains($message, 'authentication') ||
               str_contains($message, '401') ||
               str_contains($message, '403') ||
               str_contains($message, '429');
    }

    /**
     * Transform Geoapify results to our standard format.
     */
    private function transformResults(array $features): array
    {
        $results = [];

        foreach ($features as $feature) {
            if (!isset($feature['geometry']['coordinates']) || !isset($feature['properties'])) {
                continue;
            }

            $properties = $feature['properties'];
            $coordinates = $feature['geometry']['coordinates'];

            // Geoapify returns [lng, lat] format
            $lng = (float) $coordinates[0];
            $lat = (float) $coordinates[1];

            $formatted = $properties['formatted'] ?? '';
            
            // Build components array
            $components = $this->buildComponents($properties);

            $results[] = new GeocodeResult(
                provider: $this->getName(),
                formatted: $formatted,
                lat: $lat,
                lng: $lng,
                components: $components
            );
        }

        return $results;
    }

    /**
     * Build components array from Geoapify properties.
     */
    private function buildComponents(array $properties): array
    {
        $components = [];

        // Map common fields
        $fieldMap = [
            'house_number' => 'house_number',
            'street' => 'street',
            'city' => 'city',
            'district' => 'district',
            'state' => 'state',
            'postcode' => 'postcode',
            'country' => 'country',
            'country_code' => 'country_code',
        ];

        foreach ($fieldMap as $geoapifyField => $componentField) {
            if (isset($properties[$geoapifyField]) && !empty($properties[$geoapifyField])) {
                $components[$componentField] = $properties[$geoapifyField];
            }
        }

        // Add suburb/neighbourhood if available
        if (isset($properties['suburb']) && !empty($properties['suburb'])) {
            $components['suburb'] = $properties['suburb'];
        } elseif (isset($properties['neighbourhood']) && !empty($properties['neighbourhood'])) {
            $components['suburb'] = $properties['neighbourhood'];
        }

        return $components;
    }
}