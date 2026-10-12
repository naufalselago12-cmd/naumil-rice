<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Store;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FoodEcommerceApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_stores_endpoint_returns_json(): void
    {
        $response = $this->get('/api/v1/stores');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    public function test_campaigns_endpoint_returns_json(): void
    {
        $response = $this->get('/api/v1/campaigns');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }
}
