<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends BaseAdminController
{
    public function index(Request $request)
    {
        $store = $this->currentStore($request);
        $start = $request->query('start_date', Carbon::now()->subDays(30)->toDateString());
        $end = $request->query('end_date', Carbon::now()->toDateString());

        $orders = Order::with(['client', 'items'])->where('store_id', $store->id)
            ->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)
            ->orderByDesc('created_at')->get();

        $total = $orders->count();
        $revenue = (float) $orders->where('status', '!=', Order::STATUS_REJECT)->sum('total_amount');
        $reject = $orders->where('status', Order::STATUS_REJECT)->count();

        $breakdown = [];
        foreach (Order::ALL_STATUSES as $s) {
            $breakdown[$s] = $orders->where('status', $s)->count();
        }

        $top = OrderItem::whereHas('order', fn ($q) => $q->where('store_id', $store->id)
            ->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)
            ->where('status', '!=', Order::STATUS_REJECT))
            ->select('menu_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
            ->groupBy('menu_name')->orderByDesc('total_qty')->limit(10)->get();

        return $this->ok(['data' => [
            'start_date' => $start,
            'end_date' => $end,
            'total_orders' => $total,
            'total_revenue' => $revenue,
            'ready_orders' => $orders->where('status', Order::STATUS_READY)->count(),
            'reject_orders' => $reject,
            'avg_order_value' => $total > 0 ? $revenue / max(1, $total - $reject) : 0,
            'status_breakdown' => $breakdown,
            'top_items' => $top,
            'orders' => $orders->take(100)->values(),
        ]]);
    }
}
