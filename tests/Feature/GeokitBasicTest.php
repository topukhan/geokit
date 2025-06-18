<?php

namespace Topukhan\Geokit\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Topukhan\Geokit\Data\GeocodeResponse;
use Topukhan\Geokit\Facades\Geokit;
use Topukhan\Geokit\GeokitServiceProvider;
use Topukhan\Geokit\Services\AddressResolverService;

class GeokitBasicTest extends TestCase
{
    protected $provider = 'nominatim';
    protected function getPackageProviders($app)
    {
        return [GeokitServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Geokit' => Geokit::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Set test configuration
        $app['config']->set('geokit.default_providers', [$this->provider]); // Use only free provider for tests
        $app['config']->set('geokit.timeout', 10);
        $app['config']->set('geokit.max_results', 5);
    }

    /** @test */
    public function it_can_resolve_service_from_container()
    {
        $service = $this->app->make(AddressResolverService::class);
        
        $this->assertInstanceOf(AddressResolverService::class, $service);
    }

    /** @test */
    public function it_can_use_facade()
    {
        // This will use Nominatim (free provider)
        $response = Geokit::search('London, UK');
        
        $this->assertInstanceOf(GeocodeResponse::class, $response);
        $this->assertEquals('London, UK', $response->query);
        $this->assertTrue(is_array($response->results));
    }

    /** @test */
    public function it_returns_empty_results_for_empty_query()
    {
        $response = Geokit::search('');
        
        $this->assertInstanceOf(GeocodeResponse::class, $response);
        $this->assertEquals('', $response->query);
        $this->assertEmpty($response->results);
        $this->assertFalse($response->usedFallback);
    }

    /** @test */
    public function it_handles_whitespace_query()
    {
        $response = Geokit::search('   ');
        
        $this->assertInstanceOf(GeocodeResponse::class, $response);
        $this->assertEquals('', $response->query);
        $this->assertEmpty($response->results);
    }

    /** @test */
    public function response_has_correct_structure()
    {
        $response = Geokit::search('Paris, France');
        
        $this->assertInstanceOf(GeocodeResponse::class, $response);
        
        // Test response methods
        $this->assertIsBool($response->hasResults());
        $this->assertIsInt($response->count());
        $this->assertIsArray($response->toArray());
        $this->assertIsString($response->toJson());
        
        // Test array structure
        $array = $response->toArray();
        $this->assertArrayHasKey('query', $array);
        $this->assertArrayHasKey('results', $array);
        $this->assertArrayHasKey('usedFallback', $array);
        $this->assertArrayHasKey('failedProviders', $array);
    }

    /** @test */
    public function it_can_get_first_result()
    {
        $response = Geokit::search('Tokyo, Japan');
        
        if ($response->hasResults()) {
            $first = $response->first();
            $this->assertNotNull($first);
            $this->assertEquals($this->provider, $first->provider);
            $this->assertIsFloat($first->lat);
            $this->assertIsFloat($first->lng);
            $this->assertIsString($first->formatted);
            $this->assertIsArray($first->components);
        } else {
            $this->assertNull($response->first());
        }
    }
}