<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Category;
use App\Models\Menu;

class StoreBackendMenuCrudTest extends TestCase
{
    public function test_menu_crud_in_store_backend()
    {
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $store = Store::where('slug', 'sudirman')->first();
        $this->assertNotNull($store, 'Store sudirman must exist');

        $admin = User::where('username', 'admin_sudirman')->first();
        $this->assertNotNull($admin, 'Admin sudirman must exist');

        // 1. Check Index page
        $resIndex = $this->actingAs($admin)->get("/sudirman/backend/menus");
        $resIndex->assertStatus(200);

        // 2. Check Create page
        $resCreate = $this->actingAs($admin)->get("/sudirman/backend/menus/create");
        $resCreate->assertStatus(200);

        // 3. Test Store (Create Menu) with default HTML form values (checkbox is_active = "on")
        $postData = [
            'name' => 'Menu Uji Coba Cabang Sudirman',
            'description' => 'Deskripsi lezat menu uji coba',
            'price' => 35000,
            'initial_stock' => 50,
            'is_active' => 'on', // Exactly what browser sends with checked checkbox!
            'options' => [
                [
                    'type' => 'topping',
                    'name' => 'Sambal Bawang Extra',
                    'price' => 5000,
                ],
                [
                    'type' => 'condition',
                    'name' => '', // Empty option row that user didn't fill
                    'price' => 0,
                ]
            ],
        ];

        $resStore = $this->actingAs($admin)->post("/sudirman/backend/menus", $postData);
        $resStore->assertRedirect("/sudirman/backend/menus");

        $menu = Menu::where('name', 'Menu Uji Coba Cabang Sudirman')->first();
        $this->assertNotNull($menu, 'Created menu should exist in database');
        $this->assertTrue($menu->is_active, 'Menu should be active');
        $this->assertEquals(1, $menu->options()->count(), 'Only non-empty option should be saved');

        // 4. Check Edit page
        $resEdit = $this->actingAs($admin)->get("/sudirman/backend/menus/{$menu->id}/edit");
        $resEdit->assertStatus(200);

        // 5. Test Update Menu
        $updateData = [
            'name' => 'Menu Uji Coba Sudirman (Updated)',
            'description' => 'Deskripsi sudah diupdate',
            'price' => 40000,
            'initial_stock' => 50,
            'current_stock' => 45,
            'is_active' => 'on', // Checkbox checked
            'options' => [
                [
                    'type' => 'topping',
                    'name' => 'Telur Ceplok',
                    'price' => 6000,
                ]
            ],
        ];

        $resUpdate = $this->actingAs($admin)->put("/sudirman/backend/menus/{$menu->id}", $updateData);
        $resUpdate->assertRedirect("/sudirman/backend/menus");

        $menu->refresh();
        $this->assertEquals('Menu Uji Coba Sudirman (Updated)', $menu->name);
        $this->assertEquals(40000, $menu->price);
        $this->assertEquals(45, $menu->current_stock);
        $this->assertEquals('Telur Ceplok', $menu->options()->first()->name);

        // 6. Test Delete Menu
        $resDelete = $this->actingAs($admin)->delete("/sudirman/backend/menus/{$menu->id}");
        $resDelete->assertRedirect("/sudirman/backend/menus");
        $this->assertNull(Menu::find($menu->id), 'Menu should be successfully deleted');
    }
}
