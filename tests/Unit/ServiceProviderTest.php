<?php

namespace Topukhan\Geokit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Topukhan\Geokit\GeokitServiceProvider;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_can_instantiate_service_provider()
    {
        $this->assertTrue(class_exists(GeokitServiceProvider::class));
        
        // Create a mock application
        $app = $this->createMock(\Illuminate\Foundation\Application::class);
        
        $provider = new GeokitServiceProvider($app);
        
        $this->assertInstanceOf(GeokitServiceProvider::class, $provider);
    }

    /** @test */
    public function it_has_correct_namespace()
    {
        $reflection = new \ReflectionClass(GeokitServiceProvider::class);
        
        $this->assertEquals('Topukhan\Geokit', $reflection->getNamespaceName());
    }
}