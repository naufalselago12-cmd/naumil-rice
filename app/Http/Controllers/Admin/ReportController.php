<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->query('store_id');
        $startDate = $request->query('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->toDateString());

        $stores = Store::where('status', 'active')->get();
        $selectedStoreId = $storeId ?: null;

        $query = Order::with(['store', 'client', 'items'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($selectedStoreId) {
            $query->where('store_id', $selectedStoreId);
        }

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

        // Top Selling Items in range
        $topItems = OrderItem::whereHas('order', function ($q) use ($selectedStoreId, $startDate, $endDate) {
            $q->whereDate('created_at', '>=', $startDate)
              ->whereDate('created_at', '<=', $endDate)
              ->where('status', '!=', Order::STATUS_REJECT);
            if ($selectedStoreId) {
                $q->where('store_id', $selectedStoreId);
            }
        })
        ->select('menu_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
        ->groupBy('menu_name')
        ->orderByDesc('total_qty')
        ->limit(10)
        ->get();

        return view('admin.reports.index', compact(
            'stores',
            'selectedStoreId',
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
