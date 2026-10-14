<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_serves_frontend(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_backend_requires_authentication(): void
    {
        $response = $this->get('/backend');
        $response->assertRedirect('/backend/login');
    }

    public function test_admin_can_login_with_naufal_admin_credentials(): void
    {
        User::firstOrCreate(
            ['username' => 'naufal_admin'],
            [
                'name' => 'Administrator Naufal',
                'email' => 'naufal_admin@naumilrice.com',
                'password' => bcrypt('naufal2020'),
            ]
        );

        $response = $this->post('/backend/login', [
            'username' => 'naufal_admin',
            'password' => 'naufal2020',
        ]);

        $response->assertRedirect('/backend/dashboard');
        $this->assertAuthenticated();
    }
}
