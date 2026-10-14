<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuOption;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->query('store_id');
        $stores = Store::where('status', 'active')->get();
        $selectedStoreId = $storeId ?: ($stores->first()?->id ?? null);

        $menus = Menu::with(['store', 'category', 'options'])
            ->when($selectedStoreId, fn($q) => $q->where('store_id', $selectedStoreId))
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.menus.index', compact('menus', 'stores', 'selectedStoreId'));
    }

    public function create(Request $request)
    {
        $storeId = $request->query('store_id');
        $stores = Store::where('status', 'active')->get();
        $selectedStoreId = $storeId ?: ($stores->first()?->id ?? null);

        $categories = Category::when($selectedStoreId, fn($q) => $q->where('store_id', $selectedStoreId))->get();

        return view('admin.menus.create', compact('stores', 'categories', 'selectedStoreId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'initial_stock' => 'required|integer|min:0',
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

        DB::transaction(function () use ($validated, $imagePath, $request) {
            $menu = Menu::create([
                'store_id' => $validated['store_id'],
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'initial_stock' => $validated['initial_stock'],
                'current_stock' => $validated['initial_stock'],
                'image' => $imagePath,
                'is_active' => $request->boolean('is_active'),
            ]);

            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $opt) {
                    if (!empty($opt['name'])) {
                        $menu->options()->create([
                            'type' => $opt['type'] ?? 'topping',
                            'name' => trim($opt['name']),
                            'price' => $opt['price'] ?? 0,
                            'is_required' => false,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.menus.index', ['store_id' => $validated['store_id']])
            ->with('success', 'Menu makanan dan opsinya berhasil disimpan.');
    }

    public function edit(Menu $menu)
    {
        $stores = Store::where('status', 'active')->get();
        $categories = Category::where('store_id', $menu->store_id)->get();
        $menu->load('options');

        return view('admin.menus.edit', compact('menu', 'stores', 'categories'));
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'initial_stock' => 'required|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|image|max:3072',
            'is_active' => 'nullable',
            'options' => 'nullable|array',
            'options.*.type' => 'nullable|in:topping,upsize,condition',
            'options.*.name' => 'nullable|string|max:255',
            'options.*.price' => 'nullable|numeric|min:0',
        ]);

        $imagePath = $menu->image;
        if ($request->filled('image_url')) {
            $imagePath = $validated['image_url'];
        }
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('menus', 'public');
            $imagePath = asset('storage/' . $path);
        }

        DB::transaction(function () use ($menu, $validated, $imagePath, $request) {
            $menu->update([
                'store_id' => $validated['store_id'],
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'initial_stock' => $validated['initial_stock'],
                'current_stock' => $validated['current_stock'],
                'image' => $imagePath,
                'is_active' => $request->boolean('is_active'),
            ]);

            // Replace options
            $menu->options()->delete();
            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $opt) {
                    if (!empty($opt['name'])) {
                        $menu->options()->create([
                            'type' => $opt['type'] ?? 'topping',
                            'name' => trim($opt['name']),
                            'price' => $opt['price'] ?? 0,
                            'is_required' => false,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.menus.index', ['store_id' => $menu->store_id])
            ->with('success', 'Menu makanan berhasil diperbarui.');
    }

    public function destroy(Menu $menu)
    {
        $storeId = $menu->store_id;
        $menu->delete();

        return redirect()->route('admin.menus.index', ['store_id' => $storeId])
            ->with('success', 'Menu berhasil dihapus.');
    }
}
