<?php

namespace App\Services\KiriminAja;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Store;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Logika bisnis "Kirim Langsung ke Rumah":
 *  - menghitung opsi & biaya pengiriman (express + instant) dari alamat toko -> alamat pembeli
 *  - membuat pickup/shipment di KiriminAja untuk sebuah order
 *  - tracking, pembatalan, dan sinkronisasi webhook
 */
class ShippingService
{
    public const STATUS_WAITING = 'waiting_shipment';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_PROBLEM = 'problem';

    public function __construct(protected KiriminAjaClient $ka)
    {
    }

    public function client(): KiriminAjaClient
    {
        return $this->ka;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper umum
    |--------------------------------------------------------------------------
    */

    /** 0812.. / +62812.. / 62812.. -> 62812.. */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }
        return $digits;
    }

    public function weightFor(Store $store, int $totalQty): int
    {
        $perItem = max(1, (int) ($store->delivery_weight_per_item ?: config('kiriminaja.package.weight_per_item')));
        return max(1, $perItem * max(1, $totalQty));
    }

    /**
     * Kurir yang diizinkan toko, dipisah express / instant, di-irisan dengan kurir aktif di akun KiriminAja.
     *
     * @return array{express:array<int,string>,instant:array<int,string>,names:array<string,string>}
     */
    public function allowedCouriers(Store $store): array
    {
        $configuredExpress = config('kiriminaja.default_express_couriers');
        $configuredInstant = config('kiriminaja.default_instant_couriers');
        $selected = array_values(array_filter((array) $store->delivery_couriers));

        $active = [];
        $names = [];
        try {
            foreach ($this->ka->couriers() as $c) {
                $active[$c['code']] = $c['type'];
                $names[$c['code']] = $c['name'];
            }
        } catch (KiriminAjaException $e) {
            // Daftar kurir gagal dimuat -> pakai konfigurasi saja
        }

        if (!empty($selected)) {
            $express = array_values(array_intersect($selected, array_merge($configuredExpress, array_keys(array_filter($active, fn ($t) => $t === 'express')))));
            $instant = array_values(array_intersect($selected, array_merge($configuredInstant, array_keys(array_filter($active, fn ($t) => $t === 'instant')))));
        } else {
            $express = $configuredExpress;
            $instant = $configuredInstant;
        }

        if (!empty($active)) {
            $express = array_values(array_filter($express, fn ($c) => ($active[$c] ?? null) === 'express'));
            $instant = array_values(array_filter($instant, fn ($c) => ($active[$c] ?? null) === 'instant'));
        }

        return ['express' => $express, 'instant' => $instant, 'names' => $names];
    }

    /*
    |--------------------------------------------------------------------------
    | Quote (ongkir)
    |--------------------------------------------------------------------------
    */

    /**
     * @param array{district_id?:int,subdistrict_id?:int,latitude?:float,longitude?:float,address?:string} $destination
     * @return array{options:array<int,array>,errors:array<int,string>,weight:int}
     */
    public function quote(Store $store, array $destination, int $totalQty, int $itemValue): array
    {
        if (!$store->hasDeliveryOriginConfigured()) {
            throw new KiriminAjaException('Alamat asal pengiriman toko belum dilengkapi oleh admin.', 422);
        }

        $weight = $this->weightFor($store, $totalQty);
        $allowed = $this->allowedCouriers($store);
        $options = [];
        $errors = [];
        $attempted = 0;
        $failed = 0;

        // 1) Express - berbasis ID kecamatan/kelurahan
        if (!empty($destination['district_id']) && !empty($allowed['express'])) {
            $attempted++;
            try {
                $payload = [
                    'origin' => (int) $store->ka_district_id,
                    'subdistrict_origin' => (int) $store->ka_subdistrict_id,
                    'destination' => (int) $destination['district_id'],
                    'weight' => $weight,
                    'length' => (int) ($store->delivery_package_length ?: config('kiriminaja.package.length')),
                    'width' => (int) ($store->delivery_package_width ?: config('kiriminaja.package.width')),
                    'height' => (int) ($store->delivery_package_height ?: config('kiriminaja.package.height')),
                    'insurance' => '0',
                    'item_value' => max(0, $itemValue),
                    'courier' => $allowed['express'],
                ];
                if (!empty($destination['subdistrict_id'])) {
                    $payload['subdistrict_destination'] = (int) $destination['subdistrict_id'];
                }
                $res = $this->ka->expressPricing($payload);
                foreach ($res['results'] ?? [] as $r) {
                    $cost = (int) round((float) ($r['cost'] ?? 0));
                    if ($cost <= 0) {
                        continue;
                    }
                    $courier = (string) ($r['service'] ?? '');
                    $options[] = [
                        'key' => "express:{$courier}:" . ($r['service_type'] ?? ''),
                        'type' => 'express',
                        'courier' => $courier,
                        'courier_name' => $allowed['names'][$courier] ?? strtoupper($courier),
                        'service_type' => (string) ($r['service_type'] ?? ''),
                        'service_name' => (string) ($r['service_name'] ?? strtoupper($courier)),
                        'cost' => $cost,
                        'etd' => isset($r['etd']) && $r['etd'] !== '' ? $this->formatExpressEtd((string) $r['etd']) : null,
                        'group' => $r['group'] ?? null,
                        'vehicle' => null,
                    ];
                }
            } catch (KiriminAjaException $e) {
                $failed++;
                $errors[] = 'Express: ' . $e->getMessage();
            }
        }

        // 2) Instant - berbasis koordinat (GoSend / Grab Express / Borzo)
        $hasCoords = isset($destination['latitude'], $destination['longitude'])
            && $destination['latitude'] !== null && $destination['longitude'] !== null;
        if ($hasCoords && !empty($allowed['instant'])) {
            if ($weight > (int) config('kiriminaja.instant.max_weight_grams')) {
                $errors[] = 'Instant: berat paket melebihi batas 40 kg.';
            } else {
                $attempted++;
                try {
                    $res = $this->ka->instantPricing([
                        'service' => $allowed['instant'],
                        'item_price' => max(0, $itemValue),
                        'origin' => [
                            'lat' => (float) $store->ka_latitude,
                            'long' => (float) $store->ka_longitude,
                            'address' => $store->delivery_origin_full_address,
                        ],
                        'destination' => [
                            'lat' => (float) $destination['latitude'],
                            'long' => (float) $destination['longitude'],
                            'address' => (string) ($destination['address'] ?? 'Alamat tujuan'),
                        ],
                        'weight' => $weight,
                        'vehicle' => config('kiriminaja.instant.vehicle'),
                        'timezone' => config('kiriminaja.instant.timezone'),
                    ]);
                    foreach ($res['result'] ?? [] as $r) {
                        $courier = (string) ($r['name'] ?? '');
                        foreach ($r['costs'] ?? [] as $c) {
                            $cost = (int) round((float) Arr::get($c, 'price.shipping_costs', 0));
                            if ($cost <= 0) {
                                continue;
                            }
                            $serviceType = (string) ($c['service_type'] ?? 'instant');
                            $options[] = [
                                'key' => "instant:{$courier}:{$serviceType}",
                                'type' => 'instant',
                                'courier' => $courier,
                                'courier_name' => $allowed['names'][$courier] ?? Str::headline($courier),
                                'service_type' => $serviceType,
                                'service_name' => Str::headline($courier) . ' ' . Str::headline($serviceType),
                                'cost' => $cost,
                                'etd' => $c['estimation'] ?? null,
                                'group' => 'instant',
                                'vehicle' => config('kiriminaja.instant.vehicle'),
                            ];
                        }
                    }
                } catch (KiriminAjaException $e) {
                    $failed++;
                    $errors[] = 'Instant: ' . $e->getMessage();
                }
            }
        }

        // Semua permintaan gagal -> lempar error pertama supaya UI menampilkan penyebab yang jelas
        // (mis. API key belum diisi / rate limit).
        if ($attempted > 0 && $failed === $attempted && empty($options)) {
            throw new KiriminAjaException(preg_replace('/^(Express|Instant): /', '', $errors[0]), 502);
        }

        usort($options, fn ($a, $b) => $a['cost'] <=> $b['cost']);

        return ['options' => $options, 'errors' => $errors, 'weight' => $weight];
    }

    public function findOption(array $options, string $type, string $courier, string $serviceType): ?array
    {
        foreach ($options as $o) {
            if ($o['type'] === $type && $o['courier'] === $courier && strcasecmp($o['service_type'], $serviceType) === 0) {
                return $o;
            }
        }
        return null;
    }

    protected function formatExpressEtd(string $etd): string
    {
        $etd = trim($etd);
        return preg_match('/^[\d\-\s]+$/', $etd) ? $etd . ' hari' : $etd;
    }

    /*
    |--------------------------------------------------------------------------
    | Shipment (pickup request) dari order
    |--------------------------------------------------------------------------
    */

    /**
     * Buat shipment di KiriminAja untuk order delivery.
     *
     * @return array<string,mixed> ringkasan: awb, pickup_number, order_id, payment_status, qr_url
     */
    public function createShipment(Order $order): array
    {
        $order->loadMissing(['store', 'items', 'client']);
        $store = $order->store;
        $dest = (array) $order->shipping_address;

        if (!$order->isDelivery() || empty($dest)) {
            throw new KiriminAjaException('Pesanan ini bukan pesanan kirim ke rumah.', 422);
        }
        if (in_array($order->shipping_status, [self::STATUS_BOOKED, self::STATUS_SHIPPED, self::STATUS_DELIVERED], true) && !empty($order->ka_order_id)) {
            throw new KiriminAjaException('Pengiriman untuk pesanan ini sudah dibuat di KiriminAja.', 422);
        }
        if (!$store->hasDeliveryOriginConfigured()) {
            throw new KiriminAjaException('Alamat asal pengiriman toko belum lengkap.', 422);
        }
        if ($order->payment_method === 'Bayar Terima Makanan') {
            throw new KiriminAjaException('Pesanan kirim ke rumah harus dibayar di muka (non-COD).', 422);
        }
        if (!in_array($order->payment_status, ['paid', 'verified'], true)) {
            throw new KiriminAjaException('Pesanan belum dibayar. Verifikasi pembayaran terlebih dahulu sebelum membuat pengiriman.', 422);
        }

        $type = $order->shipping_type;
        $partnerOrderId = $this->partnerOrderId($order);

        $response = $type === 'instant'
            ? $this->ka->createInstantOrder($this->buildInstantPayload($order, $store, $dest, $partnerOrderId))
            : $this->ka->createExpressOrder($this->buildExpressPayload($order, $store, $dest, $partnerOrderId));

        $parsed = $this->parseOrderResponse($response, $partnerOrderId);

        $order->update([
            'ka_order_id' => $parsed['order_id'] ?: $partnerOrderId,
            'ka_awb' => $parsed['awb'],
            'ka_pickup_number' => $parsed['pickup_number'],
            'ka_payload' => $response,
            'shipping_status' => self::STATUS_BOOKED,
            'shipping_booked_at' => now(),
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => sprintf(
                'Pengiriman dibuat via KiriminAja (%s %s)%s.',
                strtoupper((string) $order->shipping_courier),
                $order->shipping_service_type,
                $parsed['awb'] ? ' - AWB ' . $parsed['awb'] : ' - AWB menyusul'
            ),
        ]);

        return $parsed + ['response' => $response];
    }

    protected function partnerOrderId(Order $order): string
    {
        $base = Str::limit($order->order_number, 25, '');
        if (empty($order->ka_order_id)) {
            return $base;
        }
        // Order id partner harus unik -> beri akhiran saat membuat ulang setelah pembatalan.
        $n = 2;
        if (preg_match('/-R(\d+)$/', $order->ka_order_id, $m)) {
            $n = (int) $m[1] + 1;
        }
        return Str::limit($order->order_number, 25 - strlen("-R{$n}"), '') . "-R{$n}";
    }

    protected function senderName(Store $store, int $min, int $max): string
    {
        $name = trim(preg_replace('/[^\pL\pN ]+/u', '', $store->name));
        $name = $name !== '' ? $name : 'Naumil Store';
        if (mb_strlen($name) < $min) {
            $name = str_pad($name . ' Store', $min, '.');
        }
        return mb_substr($name, 0, $max);
    }

    protected function padAddress(string $address, int $min = 20): string
    {
        return mb_strlen($address) >= $min ? mb_substr($address, 0, 250) : str_pad($address, $min, '.');
    }

    protected function destinationFullAddress(array $d): string
    {
        $parts = array_filter([
            $d['address'] ?? null,
            $d['subdistrict_name'] ?? null,
            $d['district_name'] ?? null,
            $d['city_name'] ?? null,
            $d['province_name'] ?? null,
            $d['postal_code'] ?? null,
        ], fn ($v) => $v !== null && trim((string) $v) !== '');
        return implode(', ', $parts);
    }

    protected function packageNumbers(Order $order, Store $store): array
    {
        $qty = max(1, (int) $order->items->sum('quantity'));
        return [
            'qty' => $qty,
            'unit_weight' => max(1, (int) ($store->delivery_weight_per_item ?: config('kiriminaja.package.weight_per_item'))),
            'weight' => $this->weightFor($store, $qty),
            'length' => (int) ($store->delivery_package_length ?: config('kiriminaja.package.length')),
            'width' => (int) ($store->delivery_package_width ?: config('kiriminaja.package.width')),
            'height' => (int) ($store->delivery_package_height ?: config('kiriminaja.package.height')),
            'item_value' => (int) max(0, round($order->subtotal - $order->discount_amount)),
        ];
    }

    protected function buildExpressPayload(Order $order, Store $store, array $dest, string $partnerOrderId): array
    {
        $n = $this->packageNumbers($order, $store);

        if (!preg_match('/^\d{5}$/', (string) $store->ka_postal_code)) {
            throw new KiriminAjaException('Kode pos toko harus 5 digit. Perbaiki di pengaturan pengiriman toko.', 422);
        }
        if (!preg_match('/^\d{5}$/', (string) ($dest['postal_code'] ?? ''))) {
            throw new KiriminAjaException('Kode pos tujuan pada pesanan tidak valid (harus 5 digit).', 422);
        }
        if (empty($dest['district_id']) || empty($dest['subdistrict_id'])) {
            throw new KiriminAjaException('Kecamatan / kelurahan tujuan pada pesanan belum lengkap.', 422);
        }

        $items = $order->items->map(fn ($it) => [
            'name' => Str::limit($it->menu_name, 60, ''),
            'price' => (int) round($it->price),
            'weight' => $n['unit_weight'],
            'width' => $n['width'],
            'height' => $n['height'],
            'length' => $n['length'],
            'qty' => (int) $it->quantity,
            'metadata' => ['sku' => 'MENU-' . $it->menu_id],
        ])->values()->all();

        $first = $order->items->first();
        $itemName = $first ? $first->menu_name . ($order->items->count() > 1 ? ' +' . ($order->items->count() - 1) . ' menu' : '') : 'Pesanan makanan';

        $note = trim(implode(' | ', array_filter([
            !empty($dest['address_note']) ? 'Patokan: ' . $dest['address_note'] : null,
            'Makanan - hindari dibanting',
        ])));

        $payload = [
            'address' => $this->padAddress($store->delivery_origin_full_address),
            'phone' => self::normalizePhone($store->phone),
            'kecamatan_id' => (int) $store->ka_district_id,
            'kelurahan_id' => (int) $store->ka_subdistrict_id,
            'latitude' => (float) $store->ka_latitude,
            'longitude' => (float) $store->ka_longitude,
            'platform_name' => config('kiriminaja.platform_name'),
            'name' => $this->senderName($store, 8, 14),
            'zipcode' => (string) $store->ka_postal_code,
            'schedule' => $this->pickupSchedule(),
            'payment_method' => config('kiriminaja.payment_method'),
            'packages' => [[
                'order_id' => $partnerOrderId,
                'destination_name' => Str::limit($dest['recipient_name'] ?? 'Pelanggan', 40, ''),
                'destination_phone' => self::normalizePhone($dest['phone'] ?? ''),
                'destination_address' => $this->padAddress($this->destinationFullAddress($dest)),
                'destination_kecamatan_id' => (int) $dest['district_id'],
                'destination_kelurahan_id' => (int) $dest['subdistrict_id'],
                'destination_zipcode' => (string) $dest['postal_code'],
                'destination_latitude' => (float) ($dest['latitude'] ?? 0),
                'destination_longitude' => (float) ($dest['longitude'] ?? 0),
                'weight' => $n['weight'],
                'width' => $n['width'],
                'height' => $n['height'],
                'length' => $n['length'],
                'qty' => 1,
                'item_value' => $n['item_value'],
                'shipping_cost' => (int) round($order->shipping_cost),
                'service' => $order->shipping_courier,
                'service_type' => $order->shipping_service_type,
                'insurance_amount' => 0,
                'cod' => 0,
                'package_type_id' => (int) config('kiriminaja.package.type_id'),
                'item_name' => Str::limit($itemName, 60, ''),
                'drop' => false,
                'note' => Str::limit($note, 200, ''),
                'items' => $items,
            ]],
        ];

        if ($pin = config('kiriminaja.pin')) {
            $payload['pin'] = (string) $pin;   // jangan kirim string kosong
        }

        return $payload;
    }

    protected function buildInstantPayload(Order $order, Store $store, array $dest, string $partnerOrderId): array
    {
        $n = $this->packageNumbers($order, $store);

        $items = $order->items->map(fn ($it) => [
            'name' => Str::limit($it->menu_name, 60, ''),
            'price' => (int) round($it->price * $it->quantity),
            'weight' => max(1, $n['unit_weight'] * (int) $it->quantity),
        ])->values()->all();

        $payload = [
            'address' => $this->padAddress($store->delivery_origin_full_address),
            'address_note' => Str::limit((string) $store->ka_address_note, 40, ''),
            'phone' => self::normalizePhone($store->phone),
            'latitude' => (float) $store->ka_latitude,
            'longitude' => (float) $store->ka_longitude,
            'name' => $this->senderName($store, 10, 40),
            'zipcode' => (string) $store->ka_postal_code,
            'payment_method' => config('kiriminaja.payment_method'),
            'packages' => [[
                'order_id' => $partnerOrderId,
                'destination' => [
                    'name' => Str::limit($dest['recipient_name'] ?? 'Pelanggan', 40, ''),
                    'phone' => self::normalizePhone($dest['phone'] ?? ''),
                    'latitude' => (float) ($dest['latitude'] ?? 0),
                    'longitude' => (float) ($dest['longitude'] ?? 0),
                    'address' => $this->destinationFullAddress($dest),
                    'address_note' => Str::limit((string) ($dest['address_note'] ?? ''), 100, ''),
                ],
                'shipping_cost' => (int) round($order->shipping_cost),
                'service' => $order->shipping_courier,
                'service_type' => $order->shipping_service_type ?: 'instant',
                'package_type_id' => (int) config('kiriminaja.package.type_id'),
                'vehicle' => config('kiriminaja.instant.vehicle'),
                'items' => $items,
            ]],
        ];

        if ($pin = config('kiriminaja.pin')) {
            $payload['pin'] = (string) $pin;
        }

        return $payload;
    }

    /** Jadwal pickup: slot pertama yang belum lewat & bukan hari libur; fallback +2 jam. */
    protected function pickupSchedule(): string
    {
        try {
            foreach ($this->ka->schedules() as $s) {
                if (!empty($s['libur'])) {
                    continue;
                }
                if (!empty($s['expired']) && is_numeric($s['expired']) && (int) $s['expired'] < time()) {
                    continue;
                }
                if (!empty($s['clock'])) {
                    return is_numeric($s['clock']) ? date('Y-m-d H:i:s', (int) $s['clock']) : (string) $s['clock'];
                }
            }
        } catch (KiriminAjaException $e) {
            // abaikan, gunakan fallback
        }

        return now()->addHours(2)->format('Y-m-d H:i:s');
    }

    /** Menormalkan respons v6.2 (details[] / results[]) maupun v4 (result.packages[]). */
    protected function parseOrderResponse(array $res, string $fallbackOrderId): array
    {
        $pkg = Arr::get($res, 'details.0')
            ?? Arr::get($res, 'results.0')
            ?? Arr::get($res, 'result.packages.0')
            ?? [];

        return [
            'order_id' => $pkg['order_id'] ?? $fallbackOrderId,
            'awb' => $pkg['awb'] ?? $pkg['waybill'] ?? null,
            'pickup_number' => $res['pickup_number'] ?? Arr::get($res, 'result.payment.payment_id'),
            'payment_status' => $res['payment_status'] ?? null,
            'qr_url' => $res['qr_url'] ?? Arr::get($res, 'result.payment.qr_content'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tracking & pembatalan
    |--------------------------------------------------------------------------
    */

    /**
     * Ambil status terbaru dari KiriminAja dan sinkronkan ke order.
     *
     * @return array{status:string,label:string,awb:?string,histories:array}
     */
    public function refreshTracking(Order $order): array
    {
        if (empty($order->ka_order_id) && empty($order->ka_awb)) {
            throw new KiriminAjaException('Pengiriman belum dibuat di KiriminAja.', 422);
        }

        if ($order->shipping_type === 'instant') {
            $res = $this->ka->trackInstant((string) $order->ka_order_id);
            $result = $res['result'] ?? null;
            if (!$result) {
                throw new KiriminAjaException('Data tracking belum tersedia.', 404);
            }
            $date = $result['date'] ?? [];
            $status = !empty($date['canceled_at']) ? self::STATUS_CANCELLED
                : (!empty($date['finished_at']) ? self::STATUS_DELIVERED
                : (!empty($result['driver']['name']) ? self::STATUS_SHIPPED : self::STATUS_BOOKED));
            $awb = $result['tracking_code'] ?? $order->ka_awb;
            $histories = [];
            $label = ['booked' => 'Menunggu driver', 'shipped' => 'Driver menuju tujuan', 'delivered' => 'Terkirim', 'cancelled' => 'Dibatalkan'][$status] ?? $status;
            $extra = ['driver' => $result['driver'] ?? null, 'live_tracking_url' => $result['live_tracking_url'] ?? null];
        } else {
            $res = $this->ka->trackExpress((string) ($order->ka_awb ?: $order->ka_order_id));
            $d = $res['details'] ?? [];
            $code = $res['status_code'] ?? ($d['status_code'] ?? null);
            $status = !empty($d['delivered']) ? self::STATUS_DELIVERED : $this->mapPackageStatus($code, !empty($d['shipped_at']));
            $awb = $d['awb'] ?? $order->ka_awb;
            $histories = array_map(fn ($h) => [
                'time' => isset($h['created_at']) && is_numeric($h['created_at']) ? date('c', (int) $h['created_at']) : ($h['created_at'] ?? null),
                'status' => $h['status'] ?? '',
            ], $res['histories'] ?? []);
            $label = $res['text'] ?? $status;
            $extra = [];
        }

        $this->applyShippingStatus($order, $status, $awb, 'Sinkronisasi tracking KiriminAja');

        return ['status' => $status, 'label' => $label, 'awb' => $awb, 'histories' => $histories] + $extra;
    }

    public function cancelShipment(Order $order, string $reason): array
    {
        if (empty($order->ka_order_id)) {
            throw new KiriminAjaException('Pengiriman belum dibuat di KiriminAja.', 422);
        }

        if ($order->shipping_type === 'instant') {
            $res = $this->ka->cancelInstant((string) $order->ka_order_id);
        } else {
            if (empty($order->ka_awb)) {
                throw new KiriminAjaException('AWB belum terbit. Pembatalan express membutuhkan nomor AWB - coba sinkronkan tracking dulu.', 422);
            }
            $res = $this->ka->cancelExpress((string) $order->ka_awb, $reason);
        }

        $order->update(['shipping_status' => self::STATUS_CANCELLED]);
        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => 'Pengiriman KiriminAja dibatalkan: ' . $reason,
        ]);

        return $res;
    }

    /*
    |--------------------------------------------------------------------------
    | Status mapping & webhook
    |--------------------------------------------------------------------------
    */

    public function mapPackageStatus(int|string|null $code, bool $shipped = false): string
    {
        $code = is_numeric($code) ? (int) $code : null;
        return match (true) {
            $code === 200 => self::STATUS_DELIVERED,
            in_array($code, [300, 302], true) => self::STATUS_CANCELLED,
            in_array($code, [400, 401, 402, 403, 404, 405, 406], true) => self::STATUS_RETURNED,
            in_array($code, [301, 310, 311, 333, 500, 555, 701, 702, 901, 902, 903, 904], true) => self::STATUS_PROBLEM,
            $code === 106 || $shipped => self::STATUS_SHIPPED,
            default => self::STATUS_BOOKED,
        };
    }

    /**
     * Terapkan status shipping ke order + selaraskan status pesanan dapur.
     */
    public function applyShippingStatus(Order $order, string $shippingStatus, ?string $awb = null, ?string $note = null): void
    {
        $changes = [];
        if ($awb && $awb !== $order->ka_awb) {
            $changes['ka_awb'] = $awb;
        }
        if ($shippingStatus !== $order->shipping_status) {
            $changes['shipping_status'] = $shippingStatus;
        }

        // Selaraskan status pesanan (hanya maju, tidak menimpa pesanan yang sudah dibatalkan/selesai).
        $newOrderStatus = null;
        if (!in_array($order->status, [Order::STATUS_REJECT, Order::STATUS_READY], true)) {
            if ($shippingStatus === self::STATUS_SHIPPED && $order->status !== Order::STATUS_DELIVERY) {
                $newOrderStatus = Order::STATUS_DELIVERY;
            } elseif ($shippingStatus === self::STATUS_DELIVERED) {
                $newOrderStatus = Order::STATUS_READY;
            }
        }
        if ($newOrderStatus) {
            $changes['status'] = $newOrderStatus;
        }

        if (empty($changes)) {
            return;
        }

        $order->update($changes);

        if (isset($changes['shipping_status']) || isset($changes['status'])) {
            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => trim(($note ? $note . ': ' : '') . 'status kirim ' . $shippingStatus . ($awb ? ' (AWB ' . $awb . ')' : '')),
            ]);

            try {
                broadcast(new \App\Events\OrderStatusUpdated($order->fresh(['store', 'client', 'items.menu', 'statusLogs', 'campaign'])));
            } catch (\Throwable $e) {
                // reverb mungkin tidak aktif
            }
        }
    }

    /**
     * Proses payload webhook KiriminAja (express & instant memakai bentuk yang sama).
     * Mengembalikan jumlah order yang diperbarui.
     */
    public function handleWebhook(array $payload): int
    {
        $method = (string) ($payload['method'] ?? '');
        $map = [
            'processed_packages' => self::STATUS_BOOKED,
            'shipped_packages' => self::STATUS_SHIPPED,
            'finished_packages' => self::STATUS_DELIVERED,
            'returned_packages' => self::STATUS_RETURNED,
            'canceled_packages' => self::STATUS_CANCELLED,
            'cancelled_packages' => self::STATUS_CANCELLED,
            'problem_packages' => self::STATUS_PROBLEM,
        ];
        $status = $map[$method] ?? null;
        if (!$status) {
            Log::info('[KiriminAja webhook] metode diabaikan', ['method' => $method]);
            return 0;
        }

        $updated = 0;
        foreach ((array) ($payload['data'] ?? []) as $row) {
            $orderId = $row['order_id'] ?? null;
            $awb = $row['awb'] ?? null;
            $order = Order::query()
                ->when($orderId, fn ($q) => $q->where('ka_order_id', $orderId))
                ->when(!$orderId && $awb, fn ($q) => $q->where('ka_awb', $awb))
                ->first();

            if (!$order && $awb) {
                $order = Order::where('ka_awb', $awb)->first();
            }
            if (!$order) {
                continue;
            }

            // Idempotent: event lama tidak boleh memundurkan status akhir.
            $final = [self::STATUS_DELIVERED, self::STATUS_CANCELLED, self::STATUS_RETURNED];
            if (in_array($order->shipping_status, $final, true) && $order->shipping_status !== $status && $status === self::STATUS_BOOKED) {
                continue;
            }

            $this->applyShippingStatus($order, $status, $awb, 'Webhook KiriminAja');
            $updated++;
        }

        return $updated;
    }
}

