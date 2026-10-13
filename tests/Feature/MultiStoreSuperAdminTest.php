<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use Illuminate\Support\Facades\Hash;

class MultiStoreSuperAdminTest extends TestCase
{
    public function test_root_page_loads_stores_looping()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_storefront_loads_specific_store_data()
    {
        $store = Store::where('slug', 'sudirman')->first();
        $this->assertNotNull($store);

        $response = $this->get('/sudirman');
        $response->assertStatus(200);

        $responseTebet = $this->get('/tebet');
        $responseTebet->assertStatus(200);
    }

    public function test_superadmin_login_page_loads()
    {
        $response = $this->get('/superadmin/login');
        $response->assertStatus(200);
    }

    public function test_superadmin_authentication_and_dashboard_access()
    {
        $superadmin = User::where('username', 'naufal_su')->first();
        $this->assertNotNull($superadmin);
        $this->assertTrue($superadmin->isSuperAdmin());

        // Test credentials naufal_su / naufal_super2020
        $response = $this->post('/superadmin/login', [
            'username' => 'naufal_su',
            'password' => 'naufal_super2020',
        ]);
        $response->assertRedirect('/superadmin');

        // Test dashboard access
        $dashboardResponse = $this->actingAs($superadmin)->get('/superadmin');
        $dashboardResponse->assertStatus(200);

        // Test stores list
        $storesResponse = $this->actingAs($superadmin)->get('/superadmin/stores');
        $storesResponse->assertStatus(200);

        // Test users list
        $usersResponse = $this->actingAs($superadmin)->get('/superadmin/users');
        $usersResponse->assertStatus(200);
    }

    public function test_store_admin_cannot_access_superadmin()
    {
        $storeAdmin = User::where('username', 'admin_sudirman')->first();
        $this->assertNotNull($storeAdmin);

        $response = $this->actingAs($storeAdmin)->get('/superadmin');
        // Should redirect to store backend or abort 403
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_store_backend_login_and_access_control()
    {
        $storeAdminSudirman = User::where('username', 'admin_sudirman')->first();
        $storeAdminTebet = User::where('username', 'admin_tebet')->first();
        $superadmin = User::where('username', 'naufal_su')->first();

        // 1. Store login page
        $response = $this->get('/sudirman/backend/login');
        $response->assertStatus(200);

        // 2. Sudirman admin can access Sudirman backend
        $resSdr = $this->actingAs($storeAdminSudirman)->get('/sudirman/backend');
        $resSdr->assertStatus(200);

        // 3. Sudirman admin is denied from Tebet backend
        $resDeny = $this->actingAs($storeAdminSudirman)->get('/tebet/backend');
        $this->assertTrue(in_array($resDeny->status(), [302, 403]));

        // 4. Tebet admin can access Tebet backend
        $resTbt = $this->actingAs($storeAdminTebet)->get('/tebet/backend');
        $resTbt->assertStatus(200);

        // 5. Super admin can access both backends
        $resSuperSdr = $this->actingAs($superadmin)->get('/sudirman/backend');
        $resSuperSdr->assertStatus(200);

        $resSuperTbt = $this->actingAs($superadmin)->get('/tebet/backend');
        $resSuperTbt->assertStatus(200);
    }

    public function test_superadmin_can_manage_stores_and_users()
    {
        $superadmin = User::where('username', 'naufal_su')->first();

        // Create store via superadmin
        $storeData = [
            'name' => 'Naumil Rice - Branch Kemang Test',
            'slug' => 'kemang-test',
            'code' => 'NR-KMG',
            'address' => 'Jl. Kemang Raya No. 10',
            'phone' => '081234567890',
            'status' => 'active',
        ];
        $resCreateStore = $this->actingAs($superadmin)->post('/superadmin/stores', $storeData);
        $resCreateStore->assertRedirect('/superadmin/stores');

        $createdStore = Store::where('slug', 'kemang-test')->first();
        $this->assertNotNull($createdStore);

        // Create user for that store via superadmin
        $userData = [
            'name' => 'Admin Kemang',
            'username' => 'admin_kemang',
            'email' => 'admin.kemang@naumilrice.com',
            'password' => 'admin12345',
            'role' => 'store_admin',
            'store_id' => $createdStore->id,
        ];
        $resCreateUser = $this->actingAs($superadmin)->post('/superadmin/users', $userData);
        $resCreateUser->assertRedirect('/superadmin/users');

        $createdUser = User::where('username', 'admin_kemang')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals($createdStore->id, $createdUser->store_id);

        // Clean up test records
        $createdUser->delete();
        $createdStore->delete();
    }
}
