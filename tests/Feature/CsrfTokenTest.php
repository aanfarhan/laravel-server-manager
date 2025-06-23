<?php

namespace ServerManager\LaravelServerManager\Tests\Feature;

use ServerManager\LaravelServerManager\Tests\TestCase;

class CsrfTokenTest extends TestCase
{
    public function test_csrf_token_is_generated_in_views()
    {
        $response = $this->get(route('server-manager.servers.index'));
        
        $response->assertStatus(200);
        
        // Check that the CSRF meta tag is present
        $response->assertSee('meta name="csrf-token"', false);
        
        // Check that the CSRF token has content
        $content = $response->getContent();
        $this->assertStringContainsString('csrf-token" content="', $content);
        
        // Extract the CSRF token value
        preg_match('/csrf-token" content="([^"]+)"/', $content, $matches);
        $this->assertNotEmpty($matches[1] ?? '', 'CSRF token should not be empty');
        
        // CSRF token should be exactly 40 characters (typical Laravel token length)
        $this->assertEquals(40, strlen($matches[1] ?? ''), 'CSRF token should be properly formatted');
    }
    
    public function test_csrf_token_helper_functions_are_present()
    {
        $response = $this->get(route('server-manager.servers.index'));
        
        $response->assertStatus(200);
        
        // Check that our CSRF helper functions are included
        $response->assertSee('window.getCsrfToken', false);
        $response->assertSee('window.getDefaultHeaders', false);
        $response->assertSee('window.debugCsrfToken', false);
    }
    
    public function test_csrf_token_is_valid()
    {
        // Start a session and get a CSRF token
        $this->startSession();
        $token = csrf_token();
        
        $this->assertNotEmpty($token, 'CSRF token should not be empty');
        $this->assertIsString($token, 'CSRF token should be a string');
        $this->assertEquals(40, strlen($token), 'CSRF token should be properly formatted');
    }
    
    public function test_post_request_with_csrf_token_succeeds()
    {
        // Create a server first
        $server = \ServerManager\LaravelServerManager\Models\Server::create([
            'name' => 'Test Server',
            'host' => 'test.example.com',
            'username' => 'testuser',
            'port' => 22,
            'auth_type' => 'password',
            'password' => 'testpass123',
            'status' => 'disconnected'
        ]);
        
        // Make a POST request with CSRF token
        $response = $this->postJson(route('server-manager.servers.test'), [
            'server_id' => $server->id
        ], [
            'X-CSRF-TOKEN' => csrf_token()
        ]);
        
        // Should not fail due to CSRF token issues
        // (it may fail for other reasons like SSH connection, but not CSRF)
        $this->assertNotEquals(419, $response->getStatusCode(), 'Should not get CSRF token mismatch error');
    }
}