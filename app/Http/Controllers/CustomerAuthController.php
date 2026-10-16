<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Store;
use App\Models\LoyaltyPromo;
use App\Services\CustomerNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerAuthController extends Controller
{
    /**
     * Register customer as a Pelanggan Setia (Loyalty Member)
     * Data: Nama Lengkap, Jenis Kelamin, Email Valid, Password, No WA, Pekerjaan Saat Ini, Pilihan Toko
     */
    public function registerLoyalty(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'whatsapp' => 'required|string|max:50',
            'occupation' => 'required|string|max:100',
            'address' => 'nullable|string|max:1000',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'email.required' => 'Email valid wajib diisi.',
            'password.required' => 'Password wajib diisi (minimal 6 karakter).',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'occupation.required' => 'Pekerjaan saat ini wajib diisi.',
        ]);

        $email = strtolower(trim($validated['email']));

        // Check if an existing client with this email is already a verified loyalty member
        $existing = Client::where('email', $email)->first();
        if ($existing && $existing->is_loyalty_member && $existing->password && $existing->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Email ini sudah terdaftar sebagai Pelanggan Setia aktif. Silakan masuk menggunakan password Anda.',
                'already_registered' => true,
            ], 422);
        }

        $client = DB::transaction(function () use ($validated, $email, $existing) {
            $clientData = [
                'name' => $validated['name'],
                'gender' => $validated['gender'],
                'email' => $email,
                'password' => $validated['password'],
                'whatsapp' => $validated['whatsapp'],
                'occupation' => $validated['occupation'],
                'address' => $validated['address'] ?? ($existing ? $existing->address : null),
                'is_loyalty_member' => true,
                'is_active' => false, // Requires email activation!
            ];

            if ($existing) {
                $existing->update($clientData);
                $client = $existing;
            } else {
                $client = Client::create($clientData);
            }

            // Affiliated stores selection (Pelanggan setia bisa pilih lebih dari 1 toko)
            if (!empty($validated['store_ids'])) {
                $client->stores()->syncWithoutDetaching($validated['store_ids']);
            }

            // Generate activation token
            $client->generateActivationToken();

            return $client;
        });

        // Send activation verification email
        app(CustomerNotificationService::class)->sendActivationEmail($client);

        return response()->json([
            'success' => true,
            'message' => "Pendaftaran Pelanggan Setia berhasil! Link verifikasi telah dikirimkan ke email {$client->email}. Silakan cek kotak masuk atau folder spam Anda.",
            'client_id' => $client->id,
            'email' => $client->email,
            'requires_activation' => true,
        ]);
    }

    /**
     * Customer Loyalty Login
     */
    public function loginLoyalty(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($validated['email']));
        $client = Client::with('stores')->where('email', $email)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum terdaftar. Silakan daftar sebagai Pelanggan Setia terlebih dahulu.',
            ], 422);
        }

        if (!$client->is_loyalty_member || empty($client->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ini terdaftar sebagai tamu, belum mendaftar akun Pelanggan Setia. Silakan daftar akun terlebih dahulu.',
            ], 422);
        }

        if (!Hash::check($validated['password'], $client->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password yang Anda masukkan salah. Silakan coba lagi.',
            ], 422);
        }

        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'is_unverified' => true,
                'email' => $client->email,
                'message' => 'Akun Pelanggan Setia Anda belum diverifikasi. Silakan periksa email aktivasi yang telah kami kirimkan, atau klik tombol Kirim Ulang Verifikasi.',
            ], 403);
        }

        // Set customer session
        session([
            'customer_client_id' => $client->id,
            'customer_email' => $client->email,
            'customer_name' => $client->name,
            'customer_is_loyalty' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Selamat datang kembali, {$client->name}!",
            'authenticated' => true,
            'client' => $client,
            'display_concat' => "{$client->name} ({$client->email})",
        ]);
    }

    /**
     * Resend activation email
     */
    public function resendActivation(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($validated['email']));
        $client = Client::where('email', $email)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan.',
            ], 404);
        }

        if ($client->is_active) {
            return response()->json([
                'success' => true,
                'message' => 'Akun Anda sudah berstatus aktif dan terverifikasi. Silakan langsung login.',
                'already_active' => true,
            ]);
        }

        $client->generateActivationToken();
        app(CustomerNotificationService::class)->sendActivationEmail($client);

        return response()->json([
            'success' => true,
            'message' => "Email verifikasi baru berhasil dikirimkan ke {$client->email}. Silakan periksa kotak masuk atau spam.",
        ]);
    }

    /**
     * Update affiliated stores for verified loyalty customer
     */
    public function updateStoreAffiliations(Request $request)
    {
        $clientId = session('customer_client_id');
        if (!$clientId && $request->filled('client_id')) {
            $clientId = $request->input('client_id');
        }

        if (!$clientId) {
            return response()->json(['success' => false, 'message' => 'Sesi tidak ditemukan.'], 401);
        }

        $validated = $request->validate([
            'store_ids' => 'required|array',
            'store_ids.*' => 'exists:stores,id',
        ]);

        $client = Client::findOrFail($clientId);
        $client->stores()->sync($validated['store_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Pilihan toko langganan berhasil diperbarui.',
            'stores' => $client->stores()->get(),
        ]);
    }

    /**
     * Activate customer account from email link
     */
    public function activate(Request $request, string $token)
    {
        $client = Client::with('stores')->where('activation_token', $token)->first();

        $alreadyActive = false;
        if (!$client) {
            $alreadyActive = true;
        } else {
            $client->activate();

            // Set customer session
            session([
                'customer_client_id' => $client->id,
                'customer_email' => $client->email,
                'customer_name' => $client->name,
                'customer_is_loyalty' => (bool)$client->is_loyalty_member,
            ]);
        }

        $stores = Store::where('status', 'active')->get();

        return view('customer.activated', [
            'client' => $client,
            'alreadyActive' => $alreadyActive,
            'stores' => $stores,
        ]);
    }

    /**
     * Get current logged-in customer session
     */
    public function currentSession(Request $request)
    {
        $clientId = session('customer_client_id');
        if ($clientId) {
            $client = Client::with(['stores', 'pointTransactions' => function ($q) {
                $q->limit(5);
            }])->find($clientId);

            if ($client) {
                return response()->json([
                    'success' => true,
                    'authenticated' => true,
                    'client' => $client,
                    'is_loyalty_member' => (bool)$client->is_loyalty_member,
                    'loyalty_points' => $client->loyalty_points,
                    'display_concat' => "{$client->name} ({$client->email})",
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'authenticated' => false,
            'client' => null,
            'loyalty_points' => 0,
        ]);
    }

    /**
     * Customer logout
     */
    public function logout(Request $request)
    {
        session()->forget(['customer_client_id', 'customer_email', 'customer_name', 'customer_is_loyalty']);

        return response()->json([
            'success' => true,
            'message' => 'Sesi pelanggan berhasil direset.',
        ]);
    }

    /**
     * Render the customer profile page via Inertia
     */
    public function profileView(Request $request)
    {
        $client = null;
        $clientId = session('customer_client_id');

        if ($clientId) {
            $client = Client::with([
                'stores',
                'orders' => function ($q) {
                    $q->with(['store', 'items.menu'])->orderBy('id', 'desc');
                },
                'pointTransactions' => function ($q) {
                    $q->with(['store', 'order'])->orderBy('id', 'desc');
                }
            ])->find($clientId);
        }

        $allStores = Store::where('status', 'active')->select(['id', 'name', 'code', 'slug', 'address', 'phone', 'logo'])->get();
        $activePromos = LoyaltyPromo::where('is_active', true)->with('store')->get();

        return Inertia::render('CustomerProfile', [
            'initialClient' => $client,
            'allStores' => $allStores,
            'activePromos' => $activePromos,
        ]);
    }

    /**
     * Get full profile data for customer via API
     */
    public function getProfileData(Request $request)
    {
        $client = null;
        $clientId = session('customer_client_id');

        if ($clientId) {
            $client = Client::with([
                'stores',
                'orders' => function ($q) {
                    $q->with(['store', 'items.menu'])->orderBy('id', 'desc');
                },
                'pointTransactions' => function ($q) {
                    $q->with(['store', 'order'])->orderBy('id', 'desc');
                }
            ])->find($clientId);
        }

        if (!$client && $request->filled('email')) {
            $email = strtolower(trim($request->input('email')));
            $client = Client::with([
                'stores',
                'orders' => function ($q) {
                    $q->with(['store', 'items.menu'])->orderBy('id', 'desc');
                },
                'pointTransactions' => function ($q) {
                    $q->with(['store', 'order'])->orderBy('id', 'desc');
                }
            ])->where('email', $email)->where('is_loyalty_member', true)->where('is_active', true)->first();

            if ($client) {
                session([
                    'customer_client_id' => $client->id,
                    'customer_email' => $client->email,
                    'customer_name' => $client->name,
                    'customer_is_loyalty' => true,
                ]);
            }
        }

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi pelanggan tidak ditemukan atau belum login.',
                'authenticated' => false,
            ], 401);
        }

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'client' => $client,
        ]);
    }

    /**
     * Update customer profile data
     */
    public function updateProfile(Request $request)
    {
        $clientId = session('customer_client_id');
        if (!$clientId && $request->filled('client_id')) {
            $clientId = $request->input('client_id');
        }

        if (!$clientId) {
            return response()->json(['success' => false, 'message' => 'Sesi tidak ditemukan.'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'whatsapp' => 'required|string|max:50',
            'occupation' => 'required|string|max:100',
            'address' => 'required|string|max:1000',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
        ]);

        $client = Client::findOrFail($clientId);
        $client->update([
            'name' => $validated['name'],
            'gender' => $validated['gender'],
            'whatsapp' => $validated['whatsapp'],
            'occupation' => $validated['occupation'],
            'address' => $validated['address'],
        ]);

        if (isset($validated['store_ids'])) {
            $client->stores()->sync($validated['store_ids']);
        }

        // Reload relations
        $client->load([
            'stores',
            'orders' => function ($q) {
                $q->with(['store', 'items.menu'])->orderBy('id', 'desc');
            },
            'pointTransactions' => function ($q) {
                $q->with(['store', 'order'])->orderBy('id', 'desc');
            }
        ]);

        // Update session
        session([
            'customer_name' => $client->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil Pelanggan Setia berhasil diperbarui!',
            'client' => $client,
        ]);
    }
}
