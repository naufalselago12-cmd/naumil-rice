<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientApiController extends Controller
{
    /**
     * Get clients list for suggestion dropdown
     * Formats: name [whatsapp, email]
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Client::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('last_order_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }

    /**
     * Check existing or register new client
     */
    public function checkOrRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string',
        ]);

        // Find existing by whatsapp or email, or create
        $client = Client::where('whatsapp', $validated['whatsapp'])
            ->orWhere('email', $validated['email'])
            ->first();

        if ($client) {
            $client->update([
                'name' => $validated['name'],
                'whatsapp' => $validated['whatsapp'],
                'email' => $validated['email'],
                'address' => $validated['address'] ?? $client->address,
                'last_order_at' => now(),
            ]);
        } else {
            $client = Client::create([
                'name' => $validated['name'],
                'whatsapp' => $validated['whatsapp'],
                'email' => $validated['email'],
                'address' => $validated['address'] ?? null,
                'last_order_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data client berhasil disimpan',
            'data' => $client
        ]);
    }
}
