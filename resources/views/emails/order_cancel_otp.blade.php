<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kode OTP Verifikasi Pembatalan Pesanan</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #1e293b;
      line-height: 1.6;
    }
    .wrapper {
      width: 100%;
      background-color: #f8fafc;
      padding: 30px 10px;
    }
    .container {
      max-width: 560px;
      margin: 0 auto;
      background-color: #ffffff;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
      border: 1px solid #e2e8f0;
    }
    .header {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      padding: 28px 25px;
      text-align: center;
      color: #ffffff;
    }
    .brand-title {
      font-size: 22px;
      font-weight: 800;
      margin: 0;
      color: #ffffff;
    }
    .brand-subtitle {
      font-size: 12px;
      color: #94a3b8;
      margin: 4px 0 0 0;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .content {
      padding: 30px 25px;
    }
    .alert-banner {
      background-color: #fef3c7;
      border: 1px solid #fde68a;
      border-radius: 10px;
      padding: 14px 16px;
      color: #92400e;
      font-size: 13.5px;
      margin-bottom: 22px;
    }
    .otp-card {
      background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      border: 2px dashed #cbd5e1;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      margin: 22px 0;
    }
    .otp-code {
      font-family: 'Courier New', Courier, monospace;
      font-size: 38px;
      font-weight: 800;
      letter-spacing: 8px;
      color: #0f172a;
      margin: 10px 0;
    }
    .order-box {
      background-color: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px;
      margin: 20px 0;
      font-size: 13.5px;
    }
    .order-row {
      display: flex;
      justify-content: space-between;
      padding: 4px 0;
      border-bottom: 1px solid #f1f5f9;
    }
    .order-row:last-child {
      border-bottom: none;
    }
    .footer {
      background-color: #f8fafc;
      padding: 20px 25px;
      text-align: center;
      font-size: 12px;
      color: #64748b;
      border-top: 1px solid #e2e8f0;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <div class="header">
        <h1 class="brand-title">🍚 Naumil Rice</h1>
        <p class="brand-subtitle">{{ $order->store->name ?? 'Store System' }}</p>
      </div>

      <div class="content">
        <h2 style="font-size: 19px; font-weight: 700; color: #0f172a; margin-top: 0;">
          Verifikasi Pembatalan & Pengembalian Dana
        </h2>
        <p style="font-size: 14px; color: #475569;">
          Halo <strong>{{ $order->client->name ?? 'Pelanggan' }}</strong>,<br>
          Kami menerima permohonan pembatalan untuk pesanan <strong>#{{ $order->order_number }}</strong>. Masukkan kode OTP di bawah ini pada halaman verifikasi pembatalan:
        </p>

        <div class="otp-card">
          <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px;">Kode OTP Verifikasi Anda</small>
          <div class="otp-code">{{ $otp }}</div>
          <small style="color: #dc2626; font-size: 12px; font-weight: 600;">
            ⏳ Berlaku selama 10 menit. Jangan berikan kode ini kepada siapapun.
          </small>
        </div>

        <div class="alert-banner">
          <strong>💡 Informasi Penting Pengembalian Dana (H+1):</strong><br>
          Pengembalian dana untuk transaksi Midtrans / pembayaran online akan diproses pada <strong>H+1 hari kerja</strong> ke rekening bank yang Anda cantumkan di bawah ini. Notifikasi status transfer refund akan dikirimkan ke email ini.
        </div>

        <div class="order-box">
          <strong style="color: #0f172a; display: block; margin-bottom: 8px;">📋 Rincian Pesanan & Rekening Refund:</strong>
          <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <tr>
              <td style="padding: 4px 0; color: #64748b;">Nomor Pesanan:</td>
              <td style="padding: 4px 0; font-weight: 700; text-align: right; color: #0f172a;">{{ $order->order_number }}</td>
            </tr>
            <tr>
              <td style="padding: 4px 0; color: #64748b;">Total Pembayaran:</td>
              <td style="padding: 4px 0; font-weight: 700; text-align: right; color: #16a34a;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
              <td style="padding: 4px 0; color: #64748b;">Metode Bayar:</td>
              <td style="padding: 4px 0; text-align: right; color: #0f172a;">{{ $order->payment_method }}</td>
            </tr>
            @if(!empty($bankDetails['bank_name']))
            <tr>
              <td style="padding: 4px 0; color: #64748b;">Bank Tujuan:</td>
              <td style="padding: 4px 0; font-weight: 600; text-align: right; color: #0f172a;">{{ $bankDetails['bank_name'] }}</td>
            </tr>
            @endif
            @if(!empty($bankDetails['account_number']))
            <tr>
              <td style="padding: 4px 0; color: #64748b;">Nomor Rekening:</td>
              <td style="padding: 4px 0; font-weight: 700; font-family: monospace; text-align: right; color: #0f172a;">{{ $bankDetails['account_number'] }}</td>
            </tr>
            @endif
            @if(!empty($bankDetails['account_name']))
            <tr>
              <td style="padding: 4px 0; color: #64748b;">Nama Pemilik Rekening:</td>
              <td style="padding: 4px 0; font-weight: 600; text-align: right; color: #0f172a;">{{ $bankDetails['account_name'] }}</td>
            </tr>
            @endif
          </table>
        </div>

        <p style="font-size: 13px; color: #64748b;">
          Jika Anda tidak merasa melakukan pembatalan pesanan ini, abaikan email ini atau segera hubungi admin Naumil Rice.
        </p>
      </div>

      <div class="footer">
        &copy; {{ date('Y') }} Naumil Rice. Seluruh hak cipta dilindungi undang-undang.<br>
        Email otomatis sistem &bull; Mohon tidak membalas email ini secara langsung.
      </div>
    </div>
  </div>
</body>
</html>

