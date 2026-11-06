<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

abstract class BaseAdminController extends Controller
{
    protected function currentStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    /** Pastikan resource milik cabang admin yang login. */
    protected function own(Request $request, $model, string $label = 'Data'): void
    {
        if ((int) $model->store_id !== (int) $this->currentStore($request)->id) {
            abort(403, "{$label} ini bukan milik cabang Anda.");
        }
    }

    protected function ok(array $data = [], string $message = 'OK', int $status = 200)
    {
        return response()->json(array_merge(['success' => true, 'message' => $message], $data), $status);
    }
}
