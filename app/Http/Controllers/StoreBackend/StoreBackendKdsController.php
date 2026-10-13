<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreBackendKdsController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        return view('store_backend.kds.index', compact('store'));
    }
}
