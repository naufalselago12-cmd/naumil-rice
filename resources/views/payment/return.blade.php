<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran Selesai - Naumil Store</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      color: #1e293b;
    }
    .card {
      background: #ffffff;
      max-width: 420px;
      margin: 20px;
      padding: 32px 24px;
      border-radius: 16px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
      text-align: center;
      border: 1px solid #e2e8f0;
    }
    .icon-bubble {
      width: 72px;
      height: 72px;
      background: #ecfdf5;
      color: #059669;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 36px;
    }
    h2 {
      margin: 0 0 8px;
      font-size: 20px;
      font-weight: 800;
      color: #0f172a;
    }
    p {
      margin: 0 0 20px;
      color: #64748b;
      font-size: 14px;
      line-height: 1.5;
    }
    .order-badge {
      display: inline-block;
      background: #f1f5f9;
      padding: 6px 14px;
      border-radius: 8px;
      font-family: monospace;
      font-weight: 700;
      color: #0f172a;
      font-size: 15px;
      margin-bottom: 20px;
    }
    .btn-redirect {
      display: block;
      width: 100%;
      box-sizing: border-box;
      padding: 12px;
      background: #059669;
      color: #ffffff;
      text-decoration: none;
      font-weight: 700;
      font-size: 14px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
    }
    .spinner {
      width: 20px;
      height: 20px;
      border: 2px solid #cbd5e1;
      border-top-color: #059669;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      display: inline-block;
      vertical-align: middle;
      margin-right: 8px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon-bubble">✓</div>
    <h2>Pembayaran Berhasil!</h2>
    <p>Terima kasih. Pembayaran pesanan Anda telah diterima oleh sistem dapur Naumil Store.</p>

    @if(!empty($orderNumber))
    <div class="order-badge">#{{ $orderNumber }}</div>
    @endif

    <div style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
      <span class="spinner"></span> Menutup jendela dan mengarahkan ke halaman pelacakan pesanan...
    </div>

    @if(!empty($orderNumber))
    <a href="{{ url('/trace/' . $orderNumber) }}" class="btn-redirect" id="manualBtn">
      Buka Pelacakan Pesanan
    </a>
    @endif
  </div>

  <script>
    const orderNumber = "{{ $orderNumber }}";
    const traceUrl = "{{ url('/trace/' . $orderNumber) }}";

    // 1. Notify opener if opened as popup
    let notified = false;
    if (window.opener && !window.opener.closed) {
      try {
        window.opener.postMessage({
          type: 'MAYAR_PAYMENT_SUCCESS',
          orderNumber: orderNumber
        }, '*');
        notified = true;
      } catch (e) {
        console.error('Error posting message to opener:', e);
      }

      // Close this popup automatically
      setTimeout(function() {
        try {
          window.close();
        } catch (e) {}
      }, 1000);
    }

    // 2. If not a popup or window did not close after 2.5 seconds, navigate current window to trace page
    setTimeout(function() {
      if (orderNumber) {
        window.location.href = traceUrl;
      }
    }, 2200);
  </script>
</body>
</html>
