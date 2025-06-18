<?php

namespace Topukhan\Geokit\Contracts;

use Topukhan\Geokit\Data\GeocodeResult;

interface GeocodingDriverInterface
{
    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Check if the provider is available (has valid API key, etc.).
     */
    public function isAvailable(): bool;

    /**
     * Search for addresses matching the given query.
     *
     * @param  string  $query  The search query
     * @param  int  $maxResults  Maximum number of results to return
     * @return GeocodeResult[] Array of geocode results
     *
     * @throws \Exception When the provider fails and should be skipped
     */
    public function search(string $query, int $maxResults = 10): array;

    /**
     * Mark this provider as unavailable for the current session.
     * This is called when quota is exceeded or API key is invalid.
     */
    public function markUnavailable(): void;
}
