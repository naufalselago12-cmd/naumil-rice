<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan Anda Sudah Ready! - Naumil Rice</title>
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
      background: linear-gradient(135deg, #065f46 0%, #047857 100%);
      padding: 30px 25px;
      text-align: center;
      color: #ffffff;
    }
    .brand-title {
      font-size: 24px;
      font-weight: 800;
      margin: 0;
      color: #ffffff;
    }
    .brand-subtitle {
      font-size: 13px;
      color: #a7f3d0;
      margin: 4px 0 0 0;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .content {
      padding: 35px 30px;
    }
    .ready-banner {
      background-color: #ecfdf5;
      border: 1px solid #10b981;
      border-radius: 8px;
      padding: 15px 20px;
      margin-bottom: 25px;
      text-align: center;
    }
    .ready-banner h3 {
      margin: 0 0 5px 0;
      color: #065f46;
      font-size: 18px;
      font-weight: 800;
    }
    .ready-banner p {
      margin: 0;
      color: #047857;
      font-size: 14px;
    }
    .greeting {
      font-size: 18px;
      font-weight: 700;
      color: #111827;
      margin-top: 0;
      margin-bottom: 15px;
    }
    .btn-track {
      display: inline-block;
      padding: 14px 32px;
      background: #059669;
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      border-radius: 8px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
      margin: 20px 0;
    }
    .btn-track:hover {
      background: #047857;
    }
    .info-card {
      background-color: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 16px 20px;
      margin: 20px 0;
    }
    .item-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 14px;
    }
    .item-table th {
      text-align: left;
      padding: 8px 0;
      border-bottom: 2px solid #e5e7eb;
      color: #6b7280;
      font-size: 12px;
      text-transform: uppercase;
    }
    .item-table td {
      padding: 10px 0;
      border-bottom: 1px dashed #e5e7eb;
    }
    .total-row {
      font-size: 16px;
      font-weight: 800;
      color: #111827;
      padding-top: 12px;
    }
    .footer {
      background-color: #f9fafb;
      padding: 20px 25px;
      text-align: center;
      font-size: 12px;
      color: #6b7280;
      border-top: 1px solid #e5e7eb;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <!-- Header -->
      <div class="header">
        <h1 class="brand-title">🍚 Naumil Rice</h1>
        <p class="brand-subtitle">{{ $order->store->name ?? 'Cabang Naumil Rice' }}</p>
      </div>

      <!-- Main Body -->
      <div class="content">
        <!-- Ready Announcement -->
        <div class="ready-banner">
          <h3>🍽️ MENU TELAH READY / SIAP!</h3>
          <p>Pesanan Anda telah selesai dimasak dan dipacking oleh dapur cabang.</p>
        </div>

        <h2 class="greeting">Halo, {{ $order->client->name ?? 'Pelanggan Setia' }}!</h2>
        
        <p>
          Kabar gembira! Pesanan Anda dengan nomor <strong>#{{ $order->order_number }}</strong> di <strong>{{ $order->store->name }}</strong> saat ini telah berstatus <strong>ORDER READY</strong> dan siap untuk diambil / diantarkan sesuai instruksi pesanan.
        </p>

        <!-- Order Summary Card -->
        <div class="info-card">
          <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 12px;">
            <tr>
              <td style="padding: 4px 0; color: #6b7280; width: 35%;">Nomor Pesanan:</td>
              <td style="padding: 4px 0; font-weight: 700; color: #dc2626;">{{ $order->order_number }}</td>
            </tr>
            <tr>
              <td style="padding: 4px 0; color: #6b7280;">Cabang Dapur:</td>
              <td style="padding: 4px 0; font-weight: 600; color: #111827;">{{ $order->store->name }}</td>
            </tr>
            <tr>
              <td style="padding: 4px 0; color: #6b7280;">Metode Pembayaran:</td>
              <td style="padding: 4px 0; font-weight: 600; color: #111827;">
                {{ $order->payment_method }} 
                <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: #e5e7eb; color: #374151;">
                  {{ strtoupper($order->payment_status) }}
                </span>
              </td>
            </tr>
            @if($order->client->address)
            <tr>
              <td style="padding: 4px 0; color: #6b7280;">Alamat Pengiriman:</td>
              <td style="padding: 4px 0; font-weight: 500; color: #111827;">{{ $order->client->address }}</td>
            </tr>
            @endif
          </table>

          <!-- Items Table -->
          <table class="item-table">
            <thead>
              <tr>
                <th>Menu</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->items as $item)
              <tr>
                <td>
                  <strong>{{ $item->menu_name }}</strong>
                  @if(!empty($item->selected_options) && is_array($item->selected_options))
                    <div style="font-size: 12px; color: #6b7280;">
                      + {{ collect($item->selected_options)->pluck('name')->join(', ') }}
                    </div>
                  @endif
                  @if($item->special_notes)
                    <div style="font-size: 12px; color: #d97706;">
                      Catatan: {{ $item->special_notes }}
                    </div>
                  @endif
                </td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right; font-weight: 600;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2" class="total-row">Total Akhir:</td>
                <td style="text-align: right;" class="total-row">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Action Button -->
        <div style="text-align: center;">
          <a href="{{ $traceUrl }}" class="btn-track" target="_blank">
            🔍 Lacak Status Pesanan (Live Tracing)
          </a>
        </div>

        @if($order->store && $order->store->phone)
        <p style="text-align: center; font-size: 13px; color: #6b7280; margin-top: 15px;">
          Butuh konfirmasi langsung dengan pihak dapur? Hubungi WhatsApp Cabang:<br>
          <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->store->phone) }}" style="color: #059669; font-weight: 600;" target="_blank">
            💬 WhatsApp {{ $order->store->name }} ({{ $order->store->phone }})
          </a>
        </p>
        @endif
      </div>

      <!-- Footer -->
      <div class="footer">
        <p style="margin: 0 0 5px 0;">Notifikasi otomatis dari <strong>Naumil Rice</strong> melalui domain <em>application@naumil.store</em>.</p>
        <p style="margin: 0;">&copy; {{ date('Y') }} Naumil Rice. Selamat menikmati hidangan kami!</p>
      </div>
    </div>
  </div>
</body>
</html>
