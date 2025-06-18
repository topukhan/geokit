<?php

namespace Topukhan\Geokit\Data;

class GeocodeResponse
{
    /**
     * @param  string  $query  The original search query
     * @param  GeocodeResult[]  $results  Array of geocode results
     * @param  bool  $usedFallback  Whether fallback providers were used
     * @param  array  $failedProviders  List of providers that failed
     */
    public function __construct(
        public readonly string $query,
        public readonly array $results,
        public readonly bool $usedFallback = false,
        public readonly array $failedProviders = []
    ) {}

    /**
     * Check if the response has any results.
     */
    public function hasResults(): bool
    {
        return ! empty($this->results);
    }

    /**
     * Get the number of results.
     */
    public function count(): int
    {
        return count($this->results);
    }

    /**
     * Get the first result, if any.
     */
    public function first(): ?GeocodeResult
    {
        return $this->results[0] ?? null;
    }

    /**
     * Convert the response to an array.
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'results' => array_map(fn (GeocodeResult $result) => $result->toArray(), $this->results),
            'usedFallback' => $this->usedFallback,
            'failedProviders' => $this->failedProviders,
        ];
    }

    /**
     * Convert the response to JSON.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
