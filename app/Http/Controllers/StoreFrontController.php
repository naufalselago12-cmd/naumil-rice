<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Campaign;
use Inertia\Inertia;
use Illuminate\Http\Request;

class StoreFrontController extends Controller
{
    /**
     * Root URL (/) - Directory of all stores (Looping per toko)
     */
    public function directory()
    {
        $stores = Store::where('status', 'active')
            ->withCount([
                'menus' => fn($q) => $q->where('is_active', true),
                'campaigns' => fn($q) => $q->where('is_active', true),
            ])
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'code' => $store->code,
                    'slug' => $store->slug ?: \Illuminate\Support\Str::slug($store->name),
                    'address' => $store->address,
                    'phone' => $store->phone,
                    'logo' => $store->logo,
                    'qris_image' => $store->qris_image,
                    'status' => $store->status,
                    'menus_count' => $store->menus_count,
                    'campaigns_count' => $store->campaigns_count,
                    'frontend_url' => url('/' . ($store->slug ?: $store->code)),
                    'backend_url' => url('/' . ($store->slug ?: $store->code) . '/backend'),
                ];
            });

        return Inertia::render('Home', [
            'stores' => $stores,
        ]);
    }

    /**
     * Store Front (/{nama_toko}) - All data bound to the selected store
     */
    public function show(string $store_slug)
    {
        $store = Store::where('slug', $store_slug)
            ->orWhere('code', $store_slug)
            ->first();

        if (!$store) {
            return redirect()->route('home')->with('error', "Cabang '{$store_slug}' tidak ditemukan.");
        }

        // 1. Categories for this store with their menus
        $categories = Category::where('store_id', $store->id)
            ->with(['menus' => function ($q) {
                $q->where('is_active', true)->with('options');
            }])
            ->orderBy('display_order', 'asc')
            ->get();

        // 2. All active menus for this store
        $menus = Menu::where('store_id', $store->id)
            ->where('is_active', true)
            ->with(['category', 'options'])
            ->get();

        // 3. Active Pre-Order campaigns for this store
        $campaigns = Campaign::where('store_id', $store->id)
            ->where('is_active', true)
            ->with('menus')
            ->get();

        // 4. Other stores for quick switching
        $otherStores = Store::where('status', 'active')
            ->where('id', '!=', $store->id)
            ->select('id', 'name', 'code', 'slug', 'address')
            ->get();

        return Inertia::render('StoreFront', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
                'slug' => $store->slug ?: \Illuminate\Support\Str::slug($store->name),
                'address' => $store->address,
                'phone' => $store->phone,
                'logo' => $store->logo,
                'qris_image' => $store->qris_image,
                'qris_data' => $store->qris_data,
                'status' => $store->status,
                'backend_url' => url('/' . ($store->slug ?: $store->code) . '/backend'),
            ],
            'categories' => $categories,
            'menus' => $menus,
            'campaigns' => $campaigns,
            'otherStores' => $otherStores,
        ]);
    }
}
