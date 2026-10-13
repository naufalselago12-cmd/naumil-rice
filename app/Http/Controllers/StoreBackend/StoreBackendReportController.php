<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreBackendReportController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $startDate = $request->query('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->toDateString());

        $query = Order::with(['client', 'items'])
            ->where('store_id', $store->id)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $allOrders = (clone $query)->orderBy('created_at', 'desc')->get();

        $totalOrders = $allOrders->count();
        $totalRevenue = $allOrders->where('status', '!=', Order::STATUS_REJECT)->sum('total_amount');
        $readyOrders = $allOrders->where('status', Order::STATUS_READY)->count();
        $rejectOrders = $allOrders->where('status', Order::STATUS_REJECT)->count();
        $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / max(1, $totalOrders - $rejectOrders)) : 0;

        // Status breakdown
        $statusBreakdown = [
            Order::STATUS_CONFIRMED => $allOrders->where('status', Order::STATUS_CONFIRMED)->count(),
            Order::STATUS_PROCESSING => $allOrders->where('status', Order::STATUS_PROCESSING)->count(),
            Order::STATUS_PACKAGING => $allOrders->where('status', Order::STATUS_PACKAGING)->count(),
            Order::STATUS_DELIVERY => $allOrders->where('status', Order::STATUS_DELIVERY)->count(),
            Order::STATUS_READY => $readyOrders,
            Order::STATUS_REJECT => $rejectOrders,
        ];

        // Top Selling Items for this store
        $topItems = OrderItem::whereHas('order', function ($q) use ($store, $startDate, $endDate) {
            $q->where('store_id', $store->id)
              ->whereDate('created_at', '>=', $startDate)
              ->whereDate('created_at', '<=', $endDate)
              ->where('status', '!=', Order::STATUS_REJECT);
        })
        ->select('menu_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
        ->groupBy('menu_name')
        ->orderByDesc('total_qty')
        ->limit(10)
        ->get();

        return view('store_backend.reports.index', compact(
            'store',
            'startDate',
            'endDate',
            'totalOrders',
            'totalRevenue',
            'readyOrders',
            'rejectOrders',
            'avgOrderValue',
            'statusBreakdown',
            'topItems',
            'allOrders'
        ));
    }
}
