<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreNotification;
use Illuminate\Http\Request;

class StoreBackendNotificationController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    /**
     * Halaman riwayat notifikasi realtime.
     */
    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $initial = $this->query($store, $request)->limit(20)->get();

        return view('store_backend.notifications.index', [
            'store' => $store,
            'initial' => $this->payload($store, $initial, 20),
        ]);
    }

    /**
     * Endpoint JSON: polling notifikasi baru (after_id) & paginasi riwayat (before_id).
     */
    public function feed(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $limit = min(max((int) $request->query('limit', 20), 1), 50);

        $items = $this->query($store, $request)->limit($limit)->get();

        return response()->json($this->payload($store, $items, $limit));
    }

    public function markRead(Request $request, string $store_slug, int $notification)
    {
        $store = $this->getStore($request);

        StoreNotification::where('store_id', $store->id)
            ->where('id', $notification)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'unread_count' => $this->unreadCount($store),
        ]);
    }

    public function markAllRead(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        StoreNotification::where('store_id', $store->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'unread_count' => 0]);
    }

    protected function query(Store $store, Request $request)
    {
        $query = StoreNotification::where('store_id', $store->id)->orderByDesc('id');

        if ($request->filled('after_id')) {
            $query->where('id', '>', (int) $request->query('after_id'));
        }
        if ($request->filled('before_id')) {
            $query->where('id', '<', (int) $request->query('before_id'));
        }

        match ($request->query('filter')) {
            'cod' => $query->where('type', StoreNotification::TYPE_COD),
            'direct' => $query->where('type', StoreNotification::TYPE_DIRECT),
            'unread' => $query->whereNull('read_at'),
            default => null,
        };

        return $query;
    }

    protected function payload(Store $store, $items, int $limit): array
    {
        $unread = $this->unreadCount($store);

        return [
            'items' => $items->map(fn (StoreNotification $n) => $this->present($store, $n))->values()->all(),
            'unread_count' => $unread,
            'latest_id' => (int) StoreNotification::where('store_id', $store->id)->max('id'),
            'has_more' => $items->count() >= $limit,
            'server_time' => now()->toIso8601String(),
        ];
    }

    protected function unreadCount(Store $store): int
    {
        return StoreNotification::where('store_id', $store->id)->whereNull('read_at')->count();
    }

    protected function present(Store $store, StoreNotification $n): array
    {
        return [
            'id' => $n->id,
            'order_id' => $n->order_id,
            'type' => $n->type,
            'event' => $n->event,
            'title' => $n->title,
            'data' => $n->data,
            'is_read' => $n->read_at !== null,
            'created_at' => $n->created_at->toIso8601String(),
            'created_human' => $n->created_at->locale('id')->isoFormat('D MMM YYYY, HH:mm'),
            'order_url' => route('store.backend.orders.show', [
                'store_slug' => $store->slug,
                'order' => $n->order_id,
            ]),
        ];
    }
}

