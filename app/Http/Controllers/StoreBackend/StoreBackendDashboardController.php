<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Menu;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;

class StoreBackendDashboardController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $orderQuery = Order::where('store_id', $store->id);

        $totalMenus = Menu::where('store_id', $store->id)->count();
        $activeCampaigns = Campaign::where('store_id', $store->id)->where('is_active', true)->count();
        $totalOrders = (clone $orderQuery)->count();
        $totalRevenue = (clone $orderQuery)->where('status', '!=', Order::STATUS_REJECT)->sum('total_amount');

        // Orders by Status for this store
        $ordersByStatus = [
            Order::STATUS_CONFIRMED => (clone $orderQuery)->where('status', Order::STATUS_CONFIRMED)->count(),
            Order::STATUS_PROCESSING => (clone $orderQuery)->where('status', Order::STATUS_PROCESSING)->count(),
            Order::STATUS_PACKAGING => (clone $orderQuery)->where('status', Order::STATUS_PACKAGING)->count(),
            Order::STATUS_DELIVERY => (clone $orderQuery)->where('status', Order::STATUS_DELIVERY)->count(),
            Order::STATUS_READY => (clone $orderQuery)->where('status', Order::STATUS_READY)->count(),
            Order::STATUS_REJECT => (clone $orderQuery)->where('status', Order::STATUS_REJECT)->count(),
        ];

        // Recent Orders for this store
        $recentOrders = (clone $orderQuery)
            ->with(['client', 'items'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('store_backend.dashboard', compact(
            'store',
            'totalMenus',
            'activeCampaigns',
            'totalOrders',
            'totalRevenue',
            'ordersByStatus',
            'recentOrders'
        ));
    }
}
