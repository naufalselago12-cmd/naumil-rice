<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Menu;
use App\Models\MenuOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminMenuController extends BaseAdminController
{
    private const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80';

    public function index(Request $request)
    {
        $menus = Menu::with(['category', 'options'])
            ->where('store_id', $this->currentStore($request)->id)
            ->orderByDesc('id')->get();

        return $this->ok(['data' => $menus]);
    }

    public function store(Request $request)
    {
        $store = $this->currentStore($request);
        $v = $this->validated($request, $store->id, false);

        $image = !empty($v['image_url']) ? $v['image_url'] : self::DEFAULT_IMAGE;
        if ($request->hasFile('image_file')) {
            $image = asset('storage/' . $request->file('image_file')->store('menus', 'public'));
        }

        $menu = DB::transaction(function () use ($v, $image, $request, $store) {
            $menu = Menu::create([
                'store_id' => $store->id,
                'category_id' => $v['category_id'] ?? null,
                'name' => $v['name'],
                'description' => $v['description'] ?? null,
                'price' => $v['price'],
                'initial_stock' => $v['initial_stock'],
                'current_stock' => $v['initial_stock'],
                'reward_points' => (int) ($v['reward_points'] ?? 0),
                'image' => $image,
                'is_active' => $request->boolean('is_active'),
            ]);
            $this->syncOptions($menu, $v['options'] ?? []);

            return $menu;
        });

        return $this->ok(['data' => $menu->load(['category', 'options'])], 'Menu baru berhasil ditambahkan.', 201);
    }

    /** POST (bukan PUT) agar upload gambar multipart bekerja. */
    public function update(Request $request, Menu $menu)
    {
        $this->own($request, $menu, 'Menu');
        $v = $this->validated($request, $menu->store_id, true);

        $image = $menu->image;
        if (!empty($v['image_url'])) {
            $image = $v['image_url'];
        }
        if ($request->hasFile('image_file')) {
            $image = asset('storage/' . $request->file('image_file')->store('menus', 'public'));
        }

        DB::transaction(function () use ($menu, $v, $image, $request) {
            $menu->update([
                'category_id' => $v['category_id'] ?? null,
                'name' => $v['name'],
                'description' => $v['description'] ?? null,
                'price' => $v['price'],
                'initial_stock' => $v['initial_stock'],
                'current_stock' => $v['current_stock'],
                'reward_points' => (int) ($v['reward_points'] ?? 0),
                'image' => $image,
                'is_active' => $request->boolean('is_active'),
            ]);
            $menu->options()->delete();
            $this->syncOptions($menu, $v['options'] ?? []);
        });

        return $this->ok(['data' => $menu->fresh(['category', 'options'])], 'Data menu berhasil diperbarui.');
    }

    public function destroy(Request $request, Menu $menu)
    {
        $this->own($request, $menu, 'Menu');
        $menu->delete();

        return $this->ok([], 'Menu berhasil dihapus.');
    }

    /** Aktif / nonaktif cepat dari daftar. */
    public function toggle(Request $request, Menu $menu)
    {
        $this->own($request, $menu, 'Menu');
        $menu->update(['is_active' => !$menu->is_active]);

        return $this->ok(['data' => $menu], $menu->is_active ? 'Menu diaktifkan.' : 'Menu dinonaktifkan.');
    }

    protected function validated(Request $request, int $storeId, bool $isUpdate): array
    {
        $rules = [
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('store_id', $storeId)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'initial_stock' => 'required|integer|min:0',
            'reward_points' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|image|max:3072',
            'is_active' => 'nullable',
            'options' => 'nullable|array',
            'options.*.type' => 'nullable|in:topping,upsize,condition',
            'options.*.name' => 'nullable|string|max:255',
            'options.*.price' => 'nullable|numeric|min:0',
        ];
        if ($isUpdate) {
            $rules['current_stock'] = 'required|integer|min:0';
        }

        return $request->validate($rules);
    }

    protected function syncOptions(Menu $menu, array $options): void
    {
        foreach ($options as $opt) {
            if (!empty($opt['name'])) {
                MenuOption::create([
                    'menu_id' => $menu->id,
                    'type' => $opt['type'] ?? 'topping',
                    'name' => trim($opt['name']),
                    'price' => $opt['price'] ?? 0,
                ]);
            }
        }
    }
}
