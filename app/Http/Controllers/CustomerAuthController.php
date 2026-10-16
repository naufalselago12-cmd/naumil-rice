<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    /**
     * Activate customer account from email link
     */
    public function activate(Request $request, string $token)
    {
        $client = Client::where('activation_token', $token)->first();

        $alreadyActive = false;
        if (!$client) {
            // Check if perhaps already activated
            $alreadyActive = true;
        } else {
            $client->activate();

            // Set customer session
            session([
                'customer_client_id' => $client->id,
                'customer_email' => $client->email,
                'customer_name' => $client->name,
            ]);
        }

        return view('customer.activated', [
            'client' => $client,
            'alreadyActive' => $alreadyActive,
        ]);
    }

    /**
     * Lookup customer by email (for quick login when session expires)
     * Returns concatenated name and details
     */
    public function lookupByEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($validated['email']));

        $client = Client::where('email', $email)->first();

        if ($client) {
            // Set session automatically
            session([
                'customer_client_id' => $client->id,
                'customer_email' => $client->email,
                'customer_name' => $client->name,
            ]);

            return response()->json([
                'success' => true,
                'found' => true,
                'client' => $client,
                'display_concat' => "{$client->name} ({$client->email})",
                'message' => "Akun terdeteksi: {$client->name}",
            ]);
        }

        return response()->json([
            'success' => true,
            'found' => false,
            'message' => 'Email belum terdaftar. Silakan lengkapi formulir di bawah ini untuk pesanan pertama.',
        ]);
    }

    /**
     * Get current logged-in customer session
     */
    public function currentSession(Request $request)
    {
        $clientId = session('customer_client_id');
        if ($clientId) {
            $client = Client::find($clientId);
            if ($client) {
                return response()->json([
                    'success' => true,
                    'authenticated' => true,
                    'client' => $client,
                    'display_concat' => "{$client->name} ({$client->email})",
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'authenticated' => false,
            'client' => null,
        ]);
    }

    /**
     * Customer logout
     */
    public function logout(Request $request)
    {
        session()->forget(['customer_client_id', 'customer_email', 'customer_name']);

        return response()->json([
            'success' => true,
            'message' => 'Sesi pelanggan berhasil direset.',
        ]);
    }
}
