<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreBackendCategoryController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $categories = Category::where('store_id', $store->id)
            ->withCount('menus')
            ->orderBy('display_order', 'asc')
            ->get();

        return view('store_backend.categories.index', compact('store', 'categories'));
    }

    public function store(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'integer|min:0',
        ]);

        $validated['store_id'] = $store->id;

        Category::create($validated);

        return redirect()->route('store.backend.categories.index', ['store_slug' => $store->slug])
            ->with('success', 'Kategori menu berhasil ditambahkan.');
    }

    public function update(Request $request, string $store_slug, Category $category)
    {
        $store = $this->getStore($request);

        if ($category->store_id !== $store->id) {
            abort(403, 'Kategori ini bukan milik cabang ini.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'integer|min:0',
        ]);

        $category->update($validated);

        return redirect()->route('store.backend.categories.index', ['store_slug' => $store->slug])
            ->with('success', 'Kategori menu berhasil diperbarui.');
    }

    public function destroy(Request $request, string $store_slug, Category $category)
    {
        $store = $this->getStore($request);

        if ($category->store_id !== $store->id) {
            abort(403, 'Kategori ini bukan milik cabang ini.');
        }

        $category->delete();

        return redirect()->route('store.backend.categories.index', ['store_slug' => $store->slug])
            ->with('success', 'Kategori menu berhasil dihapus.');
    }
}
