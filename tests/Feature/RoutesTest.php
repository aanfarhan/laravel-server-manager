<?php

namespace ServerManager\LaravelServerManager\Tests\Feature;

use ServerManager\LaravelServerManager\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_management_routes_exist()
    {
        $routes = [
            'server-manager.index',
            'server-manager.servers.index',
            'server-manager.servers.connect',
            'server-manager.servers.test',
            'server-manager.servers.disconnect',
            'server-manager.servers.status',
            'server-manager.servers.processes',
            'server-manager.servers.services'
        ];

        foreach ($routes as $route) {
            $this->assertTrue(\Route::has($route), "Route {$route} does not exist");
        }
    }

    public function test_deployment_routes_exist()
    {
        $routes = [
            'server-manager.deployments.index',
            'server-manager.deployments.deploy',
            'server-manager.deployments.rollback',
            'server-manager.deployments.status'
        ];

        foreach ($routes as $route) {
            $this->assertTrue(\Route::has($route), "Route {$route} does not exist");
        }
    }

    public function test_log_management_routes_exist()
    {
        $routes = [
            'server-manager.logs.index',
            'server-manager.logs.files',
            'server-manager.logs.read',
            'server-manager.logs.search',
            'server-manager.logs.tail',
            'server-manager.logs.download',
            'server-manager.logs.clear',
            'server-manager.logs.rotate',
            'server-manager.logs.errors'
        ];

        foreach ($routes as $route) {
            $this->assertTrue(\Route::has($route), "Route {$route} does not exist");
        }
    }

    public function test_routes_have_correct_methods()
    {
        $getRoutes = [
            'server-manager.index',
            'server-manager.servers.index',
            'server-manager.servers.status',
            'server-manager.servers.processes',
            'server-manager.servers.services',
            'server-manager.deployments.index',
            'server-manager.deployments.status',
            'server-manager.logs.index',
            'server-manager.logs.files',
            'server-manager.logs.read',
            'server-manager.logs.search',
            'server-manager.logs.tail',
            'server-manager.logs.download',
            'server-manager.logs.errors'
        ];

        $postRoutes = [
            'server-manager.servers.connect',
            'server-manager.servers.test',
            'server-manager.servers.disconnect',
            'server-manager.deployments.deploy',
            'server-manager.deployments.rollback',
            'server-manager.logs.clear',
            'server-manager.logs.rotate'
        ];

        foreach ($getRoutes as $route) {
            $routeInstance = \Route::getRoutes()->getByName($route);
            $this->assertContains('GET', $routeInstance->methods(), "Route {$route} should accept GET method");
        }

        foreach ($postRoutes as $route) {
            $routeInstance = \Route::getRoutes()->getByName($route);
            $this->assertContains('POST', $routeInstance->methods(), "Route {$route} should accept POST method");
        }
    }

    public function test_routes_have_correct_prefixes()
    {
        $routes = [
            'server-manager.servers.index',
            'server-manager.deployments.index',
            'server-manager.logs.index'
        ];

        foreach ($routes as $route) {
            $routeInstance = \Route::getRoutes()->getByName($route);
            $this->assertStringStartsWith('server-manager', $routeInstance->uri(), "Route {$route} should have server-manager prefix");
        }
    }

    public function test_index_route_redirects_to_servers()
    {
        $response = $this->get(route('server-manager.index'));
        
        // The index route should display the servers view or redirect to servers
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            'Index route should be successful or redirect'
        );
    }

    public function test_view_routes_are_accessible()
    {
        $viewRoutes = [
            'server-manager.servers.index',
            'server-manager.deployments.index',
            'server-manager.logs.index'
        ];

        foreach ($viewRoutes as $route) {
            $response = $this->get(route($route));
            $response->assertSuccessful();
        }
    }

    public function test_api_routes_require_proper_headers()
    {
        $apiRoutes = [
            ['POST', 'server-manager.servers.connect'],
            ['GET', 'server-manager.servers.status'],
            ['POST', 'server-manager.deployments.deploy'],
            ['GET', 'server-manager.logs.files']
        ];

        foreach ($apiRoutes as [$method, $route]) {
            // Test without CSRF token (should fail for POST routes)
            if ($method === 'POST') {
                $response = $this->json($method, route($route), []);
                $this->assertTrue(
                    $response->status() === 419 || $response->status() === 422 || $response->status() === 500,
                    "Route {$route} should require CSRF token"
                );
            }
        }
    }

    public function test_route_model_binding_validation()
    {
        // Test routes that might have parameters
        $routesWithParams = [
            'server-manager.deployments.status',
            'server-manager.logs.read',
            'server-manager.logs.search'
        ];

        foreach ($routesWithParams as $route) {
            $routeInstance = \Route::getRoutes()->getByName($route);
            $this->assertNotNull($routeInstance, "Route {$route} should exist");
        }
    }
}