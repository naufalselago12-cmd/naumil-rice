<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Menu;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->query('store_id');

        $stores = Store::where('status', 'active')->get();
        $selectedStore = $storeId ? Store::find($storeId) : $stores->first();

        $orderQuery = Order::query();
        if ($selectedStore) {
            $orderQuery->where('store_id', $selectedStore->id);
        }

        $totalStores = Store::count();
        $totalMenus = Menu::when($selectedStore, fn($q) => $q->where('store_id', $selectedStore->id))->count();
        $activeCampaigns = Campaign::when($selectedStore, fn($q) => $q->where('store_id', $selectedStore->id))->where('is_active', true)->count();
        $totalOrders = (clone $orderQuery)->count();
        $totalRevenue = (clone $orderQuery)->where('status', '!=', Order::STATUS_REJECT)->sum('total_amount');
        $totalClients = Client::count();

        // Orders by Status
        $ordersByStatus = [
            Order::STATUS_CONFIRMED => (clone $orderQuery)->where('status', Order::STATUS_CONFIRMED)->count(),
            Order::STATUS_PROCESSING => (clone $orderQuery)->where('status', Order::STATUS_PROCESSING)->count(),
            Order::STATUS_PACKAGING => (clone $orderQuery)->where('status', Order::STATUS_PACKAGING)->count(),
            Order::STATUS_DELIVERY => (clone $orderQuery)->where('status', Order::STATUS_DELIVERY)->count(),
            Order::STATUS_READY => (clone $orderQuery)->where('status', Order::STATUS_READY)->count(),
            Order::STATUS_REJECT => (clone $orderQuery)->where('status', Order::STATUS_REJECT)->count(),
        ];

        // Recent Orders
        $recentOrders = (clone $orderQuery)
            ->with(['store', 'client', 'items'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'stores',
            'selectedStore',
            'totalStores',
            'totalMenus',
            'activeCampaigns',
            'totalOrders',
            'totalRevenue',
            'totalClients',
            'ordersByStatus',
            'recentOrders'
        ));
    }
}
