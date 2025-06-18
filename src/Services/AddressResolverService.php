<?php

namespace Topukhan\Geokit\Services;

use Topukhan\Geokit\Contracts\GeocodingDriverInterface;
use Topukhan\Geokit\Data\GeocodeResponse;

class AddressResolverService
{
    /**
     * @param  GeocodingDriverInterface[]  $providers
     */
    public function __construct(
        private array $providers
    ) {}

    /**
     * Search for addresses using all available providers.
     */
    public function search(string $query, int $maxResults = 10): GeocodeResponse
    {
        $query = trim($query);

        if (empty($query)) {
            return new GeocodeResponse(
                query: $query,
                results: [],
                usedFallback: false,
                failedProviders: []
            );
        }

        $allResults = [];
        $failedProviders = [];
        $usedFallback = false;
        $primaryProviderFailed = false;
        $providerCount = count($this->providers);

        foreach ($this->providers as $index => $provider) {
            
            // Skip if provider is not available
            if (! $provider->isAvailable()) {
                $failedProviders[] = $provider->getName();

                continue;
            }

            try {
                $results = $provider->search($query, $maxResults);

                if (! empty($results)) {
                    $allResults = array_merge($allResults, $results);

                    // If this is not the first provider, we used fallback
                    if ($index == $providerCount - 1 || $primaryProviderFailed) {
                        $usedFallback = true;
                    }

                    // Continue to get results from all providers
                }

            } catch (\Exception $e) {
                // Mark provider as unavailable and add to failed list
                $provider->markUnavailable();
                $failedProviders[] = $provider->getName();

                // If this was the first provider, mark that primary failed
                if ($index === 0) {
                    $primaryProviderFailed = true;
                }

                // Continue to next provider
                continue;
            }
        }

        // Remove duplicates based on coordinates (within reasonable tolerance)
        $uniqueResults = $this->removeDuplicateResults($allResults);

        // Limit total results
        if (count($uniqueResults) > $maxResults) {
            $uniqueResults = array_slice($uniqueResults, 0, $maxResults);
        }

        return new GeocodeResponse(
            query: $query,
            results: $uniqueResults,
            usedFallback: $usedFallback,
            failedProviders: array_unique($failedProviders)
        );
    }

    /**
     * Remove duplicate results based on coordinates.
     */
    private function removeDuplicateResults(array $results): array
    {
        if (empty($results)) {
            return [];
        }

        $unique = [];
        $tolerance = 0.001; // ~111 meters

        foreach ($results as $result) {
            $isDuplicate = false;

            foreach ($unique as $existingResult) {
                $latDiff = abs($result->lat - $existingResult->lat);
                $lngDiff = abs($result->lng - $existingResult->lng);

                if ($latDiff < $tolerance && $lngDiff < $tolerance) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (! $isDuplicate) {
                $unique[] = $result;
            }
        }

        return $unique;
    }

    /**
     * Get available providers.
     */
    public function getAvailableProviders(): array
    {
        return array_filter($this->providers, fn ($provider) => $provider->isAvailable());
    }

    /**
     * Get all providers.
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }
}
