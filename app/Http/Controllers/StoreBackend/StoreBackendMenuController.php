<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuOption;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreBackendMenuController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $menus = Menu::with(['category', 'options'])
            ->where('store_id', $store->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('store_backend.menus.index', compact('store', 'menus'));
    }

    public function create(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $categories = Category::where('store_id', $store->id)->orderBy('display_order')->get();

        return view('store_backend.menus.create', compact('store', 'categories'));
    }

    public function store(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('categories', 'id')->where('store_id', $store->id),
            ],
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
        ]);

        $imagePath = !empty($validated['image_url']) ? $validated['image_url'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80';
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('menus', 'public');
            $imagePath = asset('storage/' . $path);
        }

        DB::transaction(function () use ($validated, $imagePath, $request, $store) {
            $menu = Menu::create([
                'store_id' => $store->id,
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'initial_stock' => $validated['initial_stock'],
                'current_stock' => $validated['initial_stock'],
                'reward_points' => (int) ($validated['reward_points'] ?? 0),
                'image' => $imagePath,
                'is_active' => $request->boolean('is_active'),
            ]);

            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $opt) {
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
        });

        return redirect()->route('store.backend.menus.index', ['store_slug' => $store->slug])
            ->with('success', 'Menu baru berhasil ditambahkan untuk cabang ini.');
    }

    public function edit(Request $request, string $store_slug, Menu $menu)
    {
        $store = $this->getStore($request);

        if ($menu->store_id !== $store->id) {
            abort(403, 'Menu ini bukan milik cabang ini.');
        }

        $categories = Category::where('store_id', $store->id)->orderBy('display_order')->get();
        $menu->load('options');

        return view('store_backend.menus.edit', compact('store', 'menu', 'categories'));
    }

    public function update(Request $request, string $store_slug, Menu $menu)
    {
        $store = $this->getStore($request);

        if ($menu->store_id !== $store->id) {
            abort(403, 'Menu ini bukan milik cabang ini.');
        }

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('categories', 'id')->where('store_id', $store->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'initial_stock' => 'required|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'reward_points' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|image|max:3072',
            'is_active' => 'nullable',
            'options' => 'nullable|array',
            'options.*.type' => 'nullable|in:topping,upsize,condition',
            'options.*.name' => 'nullable|string|max:255',
            'options.*.price' => 'nullable|numeric|min:0',
        ]);

        $imagePath = $menu->image;
        if (!empty($validated['image_url'])) {
            $imagePath = $validated['image_url'];
        }
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('menus', 'public');
            $imagePath = asset('storage/' . $path);
        }

        DB::transaction(function () use ($menu, $validated, $imagePath, $request) {
            $menu->update([
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'initial_stock' => $validated['initial_stock'],
                'current_stock' => $validated['current_stock'],
                'reward_points' => (int) ($validated['reward_points'] ?? 0),
                'image' => $imagePath,
                'is_active' => $request->boolean('is_active'),
            ]);

            // Replace options
            $menu->options()->delete();
            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $opt) {
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
        });

        return redirect()->route('store.backend.menus.index', ['store_slug' => $store->slug])
            ->with('success', 'Data menu berhasil diperbarui.');
    }

    public function destroy(Request $request, string $store_slug, Menu $menu)
    {
        $store = $this->getStore($request);

        if ($menu->store_id !== $store->id) {
            abort(403, 'Menu ini bukan milik cabang ini.');
        }

        $menu->delete();

        return redirect()->route('store.backend.menus.index', ['store_slug' => $store->slug])
            ->with('success', 'Menu berhasil dihapus dari sistem.');
    }
}
