<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Campaign;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminDashboardController extends BaseAdminController
{
    public function index(Request $request)
    {
        $store = $this->currentStore($request);
        $orders = fn () => Order::where('store_id', $store->id);

        $byStatus = [];
        foreach (Order::ALL_STATUSES as $status) {
            $byStatus[$status] = $orders()->where('status', $status)->count();
        }

        return $this->ok([
            'data' => [
                'total_menus' => Menu::where('store_id', $store->id)->count(),
                'active_campaigns' => Campaign::where('store_id', $store->id)->where('is_active', true)->count(),
                'total_orders' => $orders()->count(),
                'total_revenue' => (float) $orders()->where('status', '!=', Order::STATUS_REJECT)->sum('total_amount'),
                'today_orders' => $orders()->whereDate('created_at', today())->count(),
                'today_revenue' => (float) $orders()->whereDate('created_at', today())->where('status', '!=', Order::STATUS_REJECT)->sum('total_amount'),
                'awaiting_verification' => $orders()->where('payment_status', 'payment_on_review')->count(),
                'orders_by_status' => $byStatus,
                'recent_orders' => $orders()->with(['client', 'items'])->orderByDesc('id')->limit(10)->get(),
            ],
        ]);
    }
}
