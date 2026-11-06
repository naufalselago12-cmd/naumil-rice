<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\StoreBackend\StoreBackendNotificationController;
use Illuminate\Http\Request;

/**
 * Notifikasi untuk aplikasi mobile: memakai logika yang sama persis dengan halaman web /notification.
 */
class AdminNotificationController extends StoreBackendNotificationController
{
    public function feed(Request $request, string $store_slug = '')
    {
        return parent::feed($request, $store_slug);
    }

    public function markAllRead(Request $request, string $store_slug = '')
    {
        return parent::markAllRead($request, $store_slug);
    }

    /** Route API tidak membawa {store_slug}; parameter `notification` datang sebagai satu-satunya argumen. */
    public function read(Request $request, int $notification)
    {
        return parent::markRead($request, '', $notification);
    }
}
