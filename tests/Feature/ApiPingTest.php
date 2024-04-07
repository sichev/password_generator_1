<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiPingTest extends TestCase
{
     public function testPing(): void
     {
         $response = $this->get('/api/ping');

         $response->assertStatus(200);
         $this->assertJson($response->content());
         $this->assertContains('version', array_keys($response->json()));
     }
}
