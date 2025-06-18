<?php

namespace Topukhan\Geokit\Services\Geocoders;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Topukhan\Geokit\Contracts\GeocodingDriverInterface;
use Topukhan\Geokit\Data\GeocodeResult;

class NominatimGeocoder implements GeocodingDriverInterface
{
    private bool $isAvailable = true;

    private const BASE_URL = 'https://nominatim.openstreetmap.org/search';

    public function __construct(
        private int $timeout = 30,
        private string $userAgent = 'Geokit Laravel Package/1.0'
    ) {}

    public function getName(): string
    {
        return 'nominatim';
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function markUnavailable(): void
    {
        $this->isAvailable = false;
    }

    public function search(string $query, int $maxResults = 10): array
    {
        if (! $this->isAvailable()) {
            throw new \Exception('Nominatim provider is not available');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                ])
                ->get(self::BASE_URL, [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => min($maxResults, 50), // Nominatim max is 50
                    'addressdetails' => 1,
                    'extratags' => 0,
                    'namedetails' => 0,
                ]);

            $this->handleErrorResponse($response);

            $data = $response->json();

            if (! is_array($data)) {
                return [];
            }

            return $this->transformResults($data);

        } catch (\Exception $e) {
            // Nominatim doesn't have quota limits, but can have rate limits
            if ($this->isRateLimitError($e)) {
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

        // Check for rate limiting (HTTP 429)
        if ($statusCode === 429) {
            $this->markUnavailable();
            throw new \Exception('Nominatim API rate limit exceeded');
        }

        // Check for blocked/forbidden
        if (in_array($statusCode, [403, 508])) {
            $this->markUnavailable();
            throw new \Exception("Nominatim API blocked or rate limited (HTTP {$statusCode})");
        }

        throw new \Exception("Nominatim API error (HTTP {$statusCode}): {$body}");
    }

    /**
     * Check if the exception indicates a rate limit error.
     */
    private function isRateLimitError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'rate limit') ||
               str_contains($message, 'blocked') ||
               str_contains($message, '429') ||
               str_contains($message, '508');
    }

    /**
     * Transform Nominatim results to our standard format.
     */
    private function transformResults(array $data): array
    {
        $results = [];

        foreach ($data as $item) {
            if (! isset($item['lat']) || ! isset($item['lon'])) {
                continue;
            }

            $lat = (float) $item['lat'];
            $lng = (float) $item['lon'];

            $formatted = $item['display_name'] ?? '';

            // Build components array from address details
            $components = $this->buildComponents($item['address'] ?? []);

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
     * Build components array from Nominatim address details.
     */
    private function buildComponents(array $address): array
    {
        if (empty($address)) {
            return [];
        }

        $components = [];

        // Map Nominatim fields to our standard components
        $fieldMap = [
            'house_number' => 'house_number',
            'road' => 'street',
            'street' => 'street',
            'city' => 'city',
            'town' => 'city',
            'village' => 'city',
            'municipality' => 'city',
            'county' => 'district',
            'state_district' => 'district',
            'state' => 'state',
            'postcode' => 'postcode',
            'country' => 'country',
            'country_code' => 'country_code',
        ];

        foreach ($fieldMap as $nominatimField => $componentField) {
            if (isset($address[$nominatimField]) && ! empty($address[$nominatimField])) {
                $components[$componentField] = $address[$nominatimField];
            }
        }

        // Add suburb/neighbourhood if available
        if (isset($address['suburb']) && ! empty($address['suburb'])) {
            $components['suburb'] = $address['suburb'];
        } elseif (isset($address['neighbourhood']) && ! empty($address['neighbourhood'])) {
            $components['suburb'] = $address['neighbourhood'];
        } elseif (isset($address['quarter']) && ! empty($address['quarter'])) {
            $components['suburb'] = $address['quarter'];
        }

        // Ensure country_code is uppercase if present
        if (isset($components['country_code'])) {
            $components['country_code'] = strtoupper($components['country_code']);
        }

        return $components;
    }
}
