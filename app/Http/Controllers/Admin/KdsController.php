<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;

class KdsController extends Controller
{
    /**
     * Dedicated Fullscreen Kitchen Display System
     */
    public function index(Request $request)
    {
        $storeId = $request->query('store_id');
        $stores = Store::where('status', 'active')->get();
        $selectedStore = $storeId ? Store::find($storeId) : $stores->first();

        return view('admin.kds.index', compact('stores', 'selectedStore'));
    }
}
