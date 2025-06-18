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
                    'text'   =>  $query,
                    'apiKey' =>  $this->apiKey,
                    'limit'  =>  min($maxResults, 20),
                    'filter' => 'countrycode:bd',
                    'format' => 'json'
                ]);

            $this->handleErrorResponse($response);
            
            $data = $response->json();
            
            if (!isset($data['results']) || !is_array($data['results'])) {
                return [];
            }

            return $this->transformResults($data['results']);

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
    private function transformResults(array $responses): array
    {
        $results = [];

        foreach ($responses as $response) {
            if (!isset($response['lat']) || !isset($response['formatted'])) {
                continue;
            }

            // Geoapify returns [lng, lat] format
            $lng = (float) $response['lon'];
            $lat = (float) $response['lat'];
            
            // Build components array
            $components = $this->buildComponents($response);

            $results[] = new GeocodeResult(
                provider: $this->getName(),
                formatted: $response['formatted'],
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
    private function buildComponents(array $response): array
    {
        $components = [];

        // Map common fields
        // provider keys => custom keys
        $fieldMap = [
            'housenumber'=> 'house_number',
            'street' => 'street',
            'city' => 'city',
            'county' => 'district',
            'state' => 'state',
            'postcode' => 'postcode',
            'country' => 'country',
            'country_code' => 'country_code',
        ];

        foreach ($fieldMap as $geoapifyField => $componentField) {
            if (isset($response[$geoapifyField]) && !empty($response[$geoapifyField])) {
                $components[$componentField] = $response[$geoapifyField];
            }
        }

        // Add suburb/neighbourhood if available
        if (isset($response['suburb']) && !empty($response['suburb'])) {
            $components['suburb'] = $response['suburb'];
        } elseif (isset($response['neighbourhood']) && !empty($response['neighbourhood'])) {
            $components['suburb'] = $response['neighbourhood'];
        }

        return $components;
    }
}