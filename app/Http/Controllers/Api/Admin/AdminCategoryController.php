<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Category;
use Illuminate\Http\Request;

class AdminCategoryController extends BaseAdminController
{
    public function index(Request $request)
    {
        $items = Category::where('store_id', $this->currentStore($request)->id)
            ->withCount('menus')->orderBy('display_order')->get();

        return $this->ok(['data' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
        ]);
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['store_id'] = $this->currentStore($request)->id;

        return $this->ok(['data' => Category::create($data)], 'Kategori menu berhasil ditambahkan.', 201);
    }

    public function update(Request $request, Category $category)
    {
        $this->own($request, $category, 'Kategori');
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
        ]);
        $data['display_order'] = $data['display_order'] ?? 0;
        $category->update($data);

        return $this->ok(['data' => $category], 'Kategori menu berhasil diperbarui.');
    }

    public function destroy(Request $request, Category $category)
    {
        $this->own($request, $category, 'Kategori');
        $category->delete();

        return $this->ok([], 'Kategori menu berhasil dihapus.');
    }
}
