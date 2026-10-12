<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreApiController extends Controller
{
    public function index()
    {
        $stores = Store::where('status', 'active')
            ->withCount(['menus', 'campaigns'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stores
        ]);
    }

    public function show($id)
    {
        $store = Store::with(['categories.menus.options'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $store
        ]);
    }
}
