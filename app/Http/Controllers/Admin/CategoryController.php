<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->query('store_id');
        $stores = Store::where('status', 'active')->get();
        $selectedStoreId = $storeId ?: ($stores->first()?->id ?? null);

        $categories = Category::with('store')
            ->when($selectedStoreId, fn($q) => $q->where('store_id', $selectedStoreId))
            ->withCount('menus')
            ->orderBy('display_order', 'asc')
            ->get();

        return view('admin.categories.index', compact('categories', 'stores', 'selectedStoreId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'integer|min:0',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index', ['store_id' => $validated['store_id']])
            ->with('success', 'Kategori menu berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'integer|min:0',
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index', ['store_id' => $category->store_id])
            ->with('success', 'Kategori menu berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        $storeId = $category->store_id;
        $category->delete();

        return redirect()->route('admin.categories.index', ['store_id' => $storeId])
            ->with('success', 'Kategori menu berhasil dihapus.');
    }
}
