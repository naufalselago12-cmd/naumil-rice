<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Store;
use App\Models\ClientPointTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreBackendLoyaltyCustomerController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    /**
     * Display list of loyalty customers for this store
     */
    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $search = $request->query('search');

        $query = Client::where(function ($q) use ($store) {
            $q->whereHas('stores', function ($sq) use ($store) {
                $sq->where('stores.id', $store->id);
            })->orWhereHas('orders', function ($oq) use ($store) {
                $oq->where('store_id', $store->id);
            });
        })->where('is_loyalty_member', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('occupation', 'like', "%{$search}%");
            });
        }

        $customers = $query->with(['stores' => function ($sq) use ($store) {
            $sq->where('stores.id', $store->id);
        }])
        ->withCount(['orders' => function ($oq) use ($store) {
            $oq->where('store_id', $store->id);
        }])
        ->orderBy('id', 'desc')
        ->paginate(15)
        ->withQueryString();

        return view('store_backend.loyalty_customers.index', compact('store', 'customers', 'search'));
    }

    /**
     * Show customer detail and points history
     */
    public function show(Request $request, string $store_slug, Client $customer)
    {
        $store = $this->getStore($request);

        $customer->load(['stores' => function ($q) use ($store) {
            $q->where('stores.id', $store->id);
        }]);

        // Orders of this customer at this store
        $orders = Order::where('client_id', $customer->id)
            ->where('store_id', $store->id)
            ->with(['items'])
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        // Point transactions for this store or customer
        $transactions = ClientPointTransaction::where('client_id', $customer->id)
            ->where(function ($q) use ($store) {
                $q->where('store_id', $store->id)->orWhereNull('store_id');
            })
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();

        $storePivot = $customer->stores->first();
        $storePoints = $storePivot ? $storePivot->pivot->points : 0;

        return view('store_backend.loyalty_customers.show', compact('store', 'customer', 'orders', 'transactions', 'storePoints'));
    }

    /**
     * Adjust customer loyalty points manually by store admin
     */
    public function adjustPoints(Request $request, string $store_slug, Client $customer)
    {
        $store = $this->getStore($request);

        $validated = $request->validate([
            'points' => 'required|integer|not_in:0',
            'action' => 'required|in:add,deduct',
            'note' => 'required|string|max:255',
        ]);

        $points = abs($validated['points']);
        $note = $validated['note'];

        if ($validated['action'] === 'add') {
            $customer->addPoints($points, "Penyesuaian Manual Admin ({$store->name}): {$note}", $store->id);
            return back()->with('success', "Berhasil menambahkan {$points} credit points ke akun {$customer->name}.");
        } else {
            if ($customer->loyalty_points < $points) {
                return back()->with('error', "Saldo poin pelanggan ({$customer->loyalty_points} Pts) tidak mencukupi untuk pengurangan {$points} Pts.");
            }
            $customer->deductPoints($points, "Pengurangan Manual Admin ({$store->name}): {$note}", $store->id);
            return back()->with('success', "Berhasil memotong {$points} credit points dari akun {$customer->name}.");
        }
    }
}
