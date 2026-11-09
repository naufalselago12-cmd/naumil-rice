<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CancellationReason;
use Illuminate\Http\Request;

class CancellationReasonApiController extends Controller
{
    /**
     * Get active cancellation reasons list.
     * Query param: ?applies_to=customer|admin|all
     */
    public function index(Request $request)
    {
        $appliesTo = $request->query('applies_to');

        $query = CancellationReason::active();

        if ($appliesTo === 'customer') {
            $query->forCustomer();
        } elseif ($appliesTo === 'admin') {
            $query->forAdmin();
        }

        $reasons = $query->get();

        return response()->json([
            'success' => true,
            'data' => $reasons,
        ]);
    }
}

