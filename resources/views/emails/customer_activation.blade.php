<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aktivasi Akun Pelanggan Naumil Rice</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f4f6f8;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #333333;
      line-height: 1.6;
    }
    .wrapper {
      width: 100%;
      table-layout: fixed;
      background-color: #f4f6f8;
      padding: 30px 10px;
    }
    .container {
      max-width: 580px;
      margin: 0 auto;
      background-color: #ffffff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
      border: 1px solid #e5e7eb;
    }
    .header {
      background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
      padding: 30px 25px;
      text-align: center;
      color: #ffffff;
    }
    .brand-title {
      font-size: 24px;
      font-weight: 800;
      letter-spacing: -0.5px;
      margin: 0;
      color: #ffffff;
    }
    .brand-subtitle {
      font-size: 13px;
      color: #9ca3af;
      margin: 4px 0 0 0;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .content {
      padding: 35px 30px;
    }
    .greeting {
      font-size: 18px;
      font-weight: 700;
      color: #111827;
      margin-top: 0;
      margin-bottom: 15px;
    }
    .badge {
      display: inline-block;
      padding: 5px 12px;
      background-color: #ecfdf5;
      color: #059669;
      font-size: 12px;
      font-weight: 700;
      border-radius: 20px;
      margin-bottom: 20px;
    }
    .btn-action {
      display: inline-block;
      padding: 14px 32px;
      background: #dc2626;
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      border-radius: 8px;
      text-align: center;
      box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
      margin: 25px 0;
    }
    .btn-action:hover {
      background: #b91c1c;
    }
    .info-card {
      background-color: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 16px 20px;
      margin: 20px 0;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      font-size: 14px;
      border-bottom: 1px dashed #e5e7eb;
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .info-label {
      color: #6b7280;
    }
    .info-val {
      font-weight: 600;
      color: #111827;
    }
    .footer {
      background-color: #f9fafb;
      padding: 20px 25px;
      text-align: center;
      font-size: 12px;
      color: #6b7280;
      border-top: 1px solid #e5e7eb;
    }
    .link-alt {
      font-size: 12px;
      color: #6b7280;
      word-break: break-all;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <!-- Header -->
      <div class="header">
        <h1 class="brand-title">🍚 Naumil Rice</h1>
        <p class="brand-subtitle">Authentic Taste & Quality Ricebowl</p>
      </div>

      <!-- Main Body -->
      <div class="content">
        <span class="badge">KONFIRMASI PENDAFTARAN</span>
        <h2 class="greeting">Halo, {{ $client->name }}!</h2>
        
        <p>Terima kasih telah melakukan pemesanan di <strong>Naumil Rice</strong>.</p>
        <p>Untuk mengaktifkan akun pelanggan Anda dan mempermudah pemesanan berikutnya (cukup login dengan memasukkan email saja), silakan klik tombol aktivasi di bawah ini:</p>

        <!-- Profile Details Box -->
        <div class="info-card">
          <div style="font-weight: 700; font-size: 13px; color: #374151; margin-bottom: 8px; text-transform: uppercase;">
            Data Akun Terdaftar:
          </div>
          <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
              <td style="padding: 5px 0; color: #6b7280; width: 35%;">Nama Lengkap:</td>
              <td style="padding: 5px 0; font-weight: 600; color: #111827;">{{ $client->name }}</td>
            </tr>
            <tr>
              <td style="padding: 5px 0; color: #6b7280;">Email Terdaftar:</td>
              <td style="padding: 5px 0; font-weight: 600; color: #111827;">{{ $client->email }}</td>
            </tr>
            <tr>
              <td style="padding: 5px 0; color: #6b7280;">No. HP / WhatsApp:</td>
              <td style="padding: 5px 0; font-weight: 600; color: #111827;">{{ $client->whatsapp }}</td>
            </tr>
            @if($client->address)
            <tr>
              <td style="padding: 5px 0; color: #6b7280;">Alamat Lengkap:</td>
              <td style="padding: 5px 0; font-weight: 500; color: #111827;">{{ $client->address }}</td>
            </tr>
            @endif
          </table>
        </div>

        <!-- Button -->
        <div style="text-align: center;">
          <a href="{{ $activationUrl }}" class="btn-action" target="_blank">
            ✓ Aktifkan Akun Sekarang
          </a>
        </div>

        <p style="font-size: 13px; color: #6b7280; margin-top: 20px;">
          Setelah akun aktif, Anda akan secara otomatis menerima notifikasi email setiap kali status pesanan Anda sudah <strong>READY / Siap Saji</strong>.
        </p>

        <p class="link-alt">
          Jika tombol di atas tidak berfungsi, salin dan buka tautan berikut di browser Anda:<br>
          <a href="{{ $activationUrl }}" style="color: #dc2626;">{{ $activationUrl }}</a>
        </p>
      </div>

      <!-- Footer -->
      <div class="footer">
        <p style="margin: 0 0 5px 0;">Email ini dikirim otomatis oleh sistem <strong>Naumil Rice</strong> melalui domain <em>application@naumil.store</em>.</p>
        <p style="margin: 0;">&copy; {{ date('Y') }} Naumil Rice. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
