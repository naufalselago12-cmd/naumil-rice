<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Aktivasi Pelanggan Setia Berhasil - Naumil Rice</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    body {
      margin: 0;
      padding: 20px;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      color: #1e293b;
      box-sizing: border-box;
    }
    .card {
      background: #ffffff;
      max-width: 540px;
      width: 100%;
      padding: 36px 28px;
      border-radius: 20px;
      box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.08);
      text-align: center;
      border: 1px solid #e2e8f0;
      box-sizing: border-box;
    }
    .icon-bubble {
      width: 80px;
      height: 80px;
      background: #ecfdf5;
      color: #059669;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 38px;
      box-shadow: 0 8px 16px rgba(5, 150, 105, 0.15);
    }
    h1 {
      margin: 0 0 8px;
      font-size: 24px;
      font-weight: 800;
      color: #0f172a;
    }
    .user-greeting {
      font-size: 16px;
      font-weight: 700;
      color: #dc2626;
      margin-bottom: 12px;
    }
    p {
      margin: 0 0 20px;
      color: #64748b;
      font-size: 14px;
      line-height: 1.6;
    }
    .points-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #fef3c7;
      color: #b45309;
      font-weight: 800;
      font-size: 15px;
      padding: 8px 16px;
      border-radius: 30px;
      margin-bottom: 20px;
      border: 1px solid #fde68a;
    }
    .store-select-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
      text-align: left;
    }
    .store-select-title {
      font-size: 14px;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .store-item-label {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 10px;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      margin-bottom: 6px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      transition: all 0.2s;
    }
    .store-item-label:hover {
      border-color: #0284c7;
      background: #f0f9ff;
    }
    .btn-cart-resume {
      display: block;
      width: 100%;
      box-sizing: border-box;
      padding: 15px 20px;
      background: #059669;
      color: #ffffff;
      text-decoration: none;
      font-weight: 800;
      font-size: 16px;
      border-radius: 10px;
      border: none;
      box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
      margin-bottom: 12px;
      transition: background 0.2s;
      cursor: pointer;
    }
    .btn-cart-resume:hover {
      background: #047857;
    }
    .btn-primary {
      display: block;
      width: 100%;
      box-sizing: border-box;
      padding: 13px 20px;
      background: #dc2626;
      color: #ffffff;
      text-decoration: none;
      font-weight: 700;
      font-size: 14px;
      border-radius: 10px;
      border: none;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
      margin-bottom: 10px;
      transition: background 0.2s;
    }
    .btn-primary:hover {
      background: #b91c1c;
    }
    .btn-secondary {
      display: block;
      width: 100%;
      box-sizing: border-box;
      padding: 11px 20px;
      background: #f1f5f9;
      color: #334155;
      text-decoration: none;
      font-weight: 600;
      font-size: 13px;
      border-radius: 10px;
      border: 1px solid #cbd5e1;
    }
    .alert-saved {
      display: none;
      background: #ecfdf5;
      color: #065f46;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 12px;
      margin-top: 8px;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon-bubble">
      <i class="fa-solid fa-check"></i>
    </div>

    @if(!$alreadyActive && $client)
      <h1>Akun Pelanggan Setia Aktif!</h1>
      <div class="user-greeting">Selamat Datang, {{ $client->name }}!</div>
      <p>
        Email <strong>{{ $client->email }}</strong> telah berhasil diverifikasi. Akun Anda kini resmi terdaftar sebagai <strong>Pelanggan Setia Naumil Rice</strong> dan berhak menikmati promo eksklusif serta reward point di setiap pembelian!
      </p>
    @else
      <h1>Akun Anda Sudah Aktif</h1>
      @if($client)
      <div class="user-greeting">Halo kembali, {{ $client->name }}!</div>
      @endif
      <p>
        Akun Pelanggan Setia Anda sudah aktif dan terverifikasi. Seluruh hak keanggotaan dan credit point Anda dapat digunakan.
      </p>
    @endif

    @if($client)
      <div class="points-badge">
        <i class="fa-solid fa-coins"></i> Saldo Poin Anda: {{ number_format($client->loyalty_points, 0, ',', '.') }} Pts
      </div>

      <!-- Store Selection (Pelanggan setia bisa pilih mau jadi pelanggan toko mana, bisa pilih lebih dari 1) -->
      @if(isset($stores) && $stores->count() > 0)
        @php
          $clientStoreIds = $client->stores->pluck('id')->toArray();
        @endphp
        <div class="store-select-box">
          <div class="store-select-title">
            <i class="fa-solid fa-store text-primary"></i> Toko Cabang Langganan Anda:
          </div>
          <small class="text-muted d-block mb-2">Pilih satu atau beberapa cabang favorit Anda untuk menerima info menu baru dan promo:</small>

          <form id="storeAffiliationForm">
            @foreach($stores as $st)
              <label class="store-item-label">
                <input type="checkbox" name="store_ids[]" value="{{ $st->id }}" {{ in_array($st->id, $clientStoreIds) ? 'checked' : '' }} onchange="saveStoreAffiliations()">
                <div style="flex: 1;">
                  <div>{{ $st->name }} <span class="badge" style="background: #e2e8f0; color: #475569; font-size: 0.7rem;">{{ $st->code }}</span></div>
                  <small style="color: #64748b; font-weight: normal; font-size: 0.75rem;">{{ $st->address }}</small>
                </div>
              </label>
            @endforeach
            <div id="saveFeedback" class="alert-saved">
              <i class="fa-solid fa-circle-check"></i> Cabang langganan berhasil diperbarui!
            </div>
          </form>
        </div>
      @endif
    @endif

    <!-- Button to continue order if draft order/cart exists in browser cache -->
    <a href="javascript:void(0)" id="resumeOrderBtn" class="btn-cart-resume" style="display: none;" onclick="goToResumeOrder()">
      <i class="fa-solid fa-cart-shopping me-1"></i> Lanjutkan Pesanan Makanan Saya
    </a>

    <a href="{{ url('/') }}" class="btn btn-primary" id="exploreBtn">
      🍚 Lihat Menu & Pesan Sekarang
    </a>

    <a href="{{ url('/trace') }}" class="btn-secondary">
      🔍 Lacak Status Pesanan
    </a>
  </div>

  <script>
    // 1. Sync client session to localStorage
    @if($client)
      const clientData = {
        id: {{ $client->id }},
        name: "{{ addslashes($client->name) }}",
        email: "{{ addslashes($client->email) }}",
        whatsapp: "{{ addslashes($client->whatsapp) }}",
        address: "{{ addslashes($client->address ?? '') }}",
        gender: "{{ addslashes($client->gender ?? '') }}",
        occupation: "{{ addslashes($client->occupation ?? '') }}",
        is_loyalty_member: true,
        loyalty_points: {{ $client->loyalty_points ?? 0 }}
      };
      localStorage.setItem('naumil_customer_session', JSON.stringify(clientData));
    @endif

    // 2. Check if user has draft cart items preserved in localStorage
    const savedCart = localStorage.getItem('naumil_customer_cart_backup');
    const returnUrl = localStorage.getItem('naumil_return_store_url') || localStorage.getItem('naumil_last_store_slug');

    const resumeBtn = document.getElementById('resumeOrderBtn');
    if (savedCart && JSON.parse(savedCart)?.length > 0 && returnUrl) {
      resumeBtn.style.display = 'block';
    }

    function goToResumeOrder() {
      const targetUrl = localStorage.getItem('naumil_return_store_url');
      if (targetUrl) {
        window.location.href = targetUrl;
      } else {
        const slug = localStorage.getItem('naumil_last_store_slug');
        window.location.href = slug ? '/' + slug : '/';
      }
    }

    // 3. Save store affiliations asynchronously
    function saveStoreAffiliations() {
      const checkboxes = document.querySelectorAll('input[name="store_ids[]"]:checked');
      const storeIds = Array.from(checkboxes).map(cb => cb.value);

      fetch('/api/v1/customer/update-stores', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          client_id: {{ $client->id ?? 'null' }},
          store_ids: storeIds
        })
      }).then(res => res.json()).then(data => {
        if (data.success) {
          const feedback = document.getElementById('saveFeedback');
          if (feedback) {
            feedback.style.display = 'block';
            setTimeout(() => { feedback.style.display = 'none'; }, 3000);
          }
        }
      }).catch(err => console.error(err));
    }
  </script>
</body>
</html>
