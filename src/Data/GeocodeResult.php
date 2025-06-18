<?php

namespace Topukhan\Geokit\Data;

class GeocodeResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $formatted,
        public readonly float $lat,
        public readonly float $lng,
        public readonly array $components = []
    ) {}

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'formatted' => $this->formatted,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'components' => $this->components,
        ];
    }

    /**
     * Create a GeocodeResult from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'],
            formatted: $data['formatted'],
            lat: (float) $data['lat'],
            lng: (float) $data['lng'],
            components: $data['components'] ?? []
        );
    }
}
