<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aktivasi Berhasil - Naumil Rice</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      color: #1e293b;
    }
    .card {
      background: #ffffff;
      max-width: 480px;
      margin: 20px;
      padding: 40px 32px;
      border-radius: 20px;
      box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.08);
      text-align: center;
      border: 1px solid #e2e8f0;
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
      margin: 0 auto 24px;
      font-size: 40px;
      box-shadow: 0 8px 16px rgba(5, 150, 105, 0.15);
    }
    h1 {
      margin: 0 0 10px;
      font-size: 24px;
      font-weight: 800;
      color: #0f172a;
    }
    .user-greeting {
      font-size: 16px;
      font-weight: 700;
      color: #dc2626;
      margin-bottom: 16px;
    }
    p {
      margin: 0 0 24px;
      color: #64748b;
      font-size: 14px;
      line-height: 1.6;
    }
    .info-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
      text-align: left;
      font-size: 13px;
    }
    .info-box ul {
      margin: 8px 0 0 18px;
      padding: 0;
      color: #475569;
    }
    .info-box li {
      margin-bottom: 6px;
    }
    .btn-primary {
      display: block;
      width: 100%;
      box-sizing: border-box;
      padding: 14px 20px;
      background: #dc2626;
      color: #ffffff;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      border-radius: 10px;
      border: none;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
      margin-bottom: 12px;
      transition: background 0.2s;
    }
    .btn-primary:hover {
      background: #b91c1c;
    }
    .btn-secondary {
      display: block;
      width: 100%;
      box-sizing: border-box;
      padding: 12px 20px;
      background: #f1f5f9;
      color: #334155;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      border-radius: 10px;
      border: 1px solid #cbd5e1;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon-bubble">✓</div>

    @if(!$alreadyActive && $client)
      <h1>Akun Berhasil Diaktifkan!</h1>
      <div class="user-greeting">Selamat Datang, {{ $client->name }}!</div>
      <p>
        Email <strong>{{ $client->email }}</strong> telah berhasil diverifikasi. Akun pelanggan Anda kini telah aktif sepenuhnya di Naumil Rice.
      </p>
    @else
      <h1>Akun Anda Sudah Aktif</h1>
      @if($client)
      <div class="user-greeting">Halo kembali, {{ $client->name }}!</div>
      @endif
      <p>
        Akun Anda sudah berstatus aktif dan siap digunakan untuk pemesanan berikutnya.
      </p>
    @endif

    <div class="info-box">
      <strong style="color: #0f172a;">Manfaat Akun Aktif:</strong>
      <ul>
        <li><strong>Notifikasi Otomatis:</strong> Mendapatkan email langsung ketika pesanan Anda berstatus <strong>READY / Siap Saji</strong>.</li>
        <li><strong>Login Cepat:</strong> Cukup masukkan email terdaftar untuk mengisi otomatis data nama, nomor HP, dan alamat pengiriman.</li>
        <li><strong>Pelacakan Pesanan:</strong> Memudahkan pengecekan status pesanan langsung dari email atau website.</li>
      </ul>
    </div>

    <a href="{{ url('/') }}" class="btn-primary">
      🍚 Lihat Menu & Pesan Sekarang
    </a>

    <a href="{{ url('/trace') }}" class="btn-secondary">
      🔍 Lacak Status Pesanan
    </a>
  </div>
</body>
</html>
