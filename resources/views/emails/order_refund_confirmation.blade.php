<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembatalan & Pengembalian Dana</title>
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
    .status-badge {
      display: inline-block;
      background-color: #fee2e2;
      color: #b91c1c;
      font-weight: 700;
      font-size: 13px;
      padding: 6px 14px;
      border-radius: 999px;
      margin-bottom: 14px;
    }
    .alert-banner {
      background-color: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-radius: 10px;
      padding: 16px;
      color: #166534;
      font-size: 13.5px;
      margin: 20px 0;
    }
    .order-box {
      background-color: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px;
      margin: 20px 0;
      font-size: 13.5px;
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
        <span class="status-badge">PESANAN BERHASIL DIBATALKAN</span>
        <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 10px 0;">
          Permohonan Pembatalan Diterima
        </h2>
        <p style="font-size: 14px; color: #475569;">
          Halo <strong>{{ $order->client->name ?? 'Pelanggan' }}</strong>,<br>
          Pesanan Anda dengan nomor <strong>#{{ $order->order_number }}</strong> telah berhasil dibatalkan.
        </p>

        @if($order->cancellation_reason)
        <div style="background-color: #f8fafc; border-left: 4px solid #ef4444; padding: 10px 14px; border-radius: 4px; margin: 14px 0; font-size: 13.5px;">
          <strong>Alasan Pembatalan:</strong> {{ $order->cancellation_reason }}
          @if($order->cancellation_remark)
            <br><span style="color: #64748b;">Catatan: {{ $order->cancellation_remark }}</span>
          @endif
        </div>
        @endif

        @if($order->refund_bank_name || $order->refund_bank_account_number)
        <div class="alert-banner">
          <strong style="font-size: 14px; display: block; margin-bottom: 4px;">💰 Informasi Pengembalian Dana (Refund H+1):</strong>
          Pengembalian dana sebesar <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong> akan diproses pada <strong>H+1 hari kerja</strong> ke rekening berikut:
          <ul style="margin: 8px 0 0 0; padding-left: 20px;">
            <li><strong>Bank:</strong> {{ $order->refund_bank_name }}</li>
            <li><strong>Nomor Rekening:</strong> {{ $order->refund_bank_account_number }}</li>
            <li><strong>Atas Nama:</strong> {{ $order->refund_bank_account_name }}</li>
          </ul>
          <p style="margin: 8px 0 0 0; font-size: 12.5px;">
            Bukti transfer refund akan otomatis dikirimkan ke email ini setelah dana berhasil dikembalikan oleh admin.
          </p>
        </div>
        @endif

        <div class="order-box">
          <strong style="color: #0f172a; display: block; margin-bottom: 8px;">Ringkasan Pesanan yang Dibatalkan:</strong>
          <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            @foreach($order->items as $it)
            <tr>
              <td style="padding: 4px 0; color: #475569;">{{ $it->quantity }}x {{ $it->menu_name }}</td>
              <td style="padding: 4px 0; text-align: right; font-weight: 600; color: #0f172a;">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr style="border-top: 1px solid #e2e8f0;">
              <td style="padding: 8px 0 4px 0; font-weight: 700; color: #0f172a;">Total Tagihan:</td>
              <td style="padding: 8px 0 4px 0; text-align: right; font-weight: 800; color: #0f172a;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
          </table>
        </div>

        <p style="font-size: 13px; color: #64748b;">
          Terima kasih telah mengunjungi Naumil Rice. Kami berharap dapat melayani pesanan Anda kembali di kesempatan berikutnya.
        </p>
      </div>

      <div class="footer">
        &copy; {{ date('Y') }} Naumil Rice. Seluruh hak cipta dilindungi undang-undang.<br>
        Email otomatis sistem &bull; Hubungi cabang toko jika Anda membutuhkan bantuan lebih lanjut.
      </div>
    </div>
  </div>
</body>
</html>

