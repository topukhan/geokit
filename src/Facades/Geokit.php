<?php

namespace Topukhan\Geokit\Facades;

use Illuminate\Support\Facades\Facade;
use Topukhan\Geokit\Data\GeocodeResponse;

/**
 * @method static GeocodeResponse search(string $query)
 *
 * @see \Topukhan\Geokit\Services\AddressResolverService
 */
class Geokit extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'geokit';
    }
}
