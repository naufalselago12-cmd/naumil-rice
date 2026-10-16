<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Mail\CustomerActivationMail;
use App\Mail\OrderReadyNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CustomerNotificationService
{
    /**
     * Send activation link email to a registered client
     */
    public function sendActivationEmail(Client $client): bool
    {
        if (empty($client->email)) {
            Log::info('Skipping activation email: client has no email', ['client_id' => $client->id]);
            return false;
        }

        try {
            if (empty($client->activation_token)) {
                $client->generateActivationToken();
            }

            Mail::to($client->email)->send(new CustomerActivationMail($client));

            Log::info('Activation email sent successfully', [
                'client_id' => $client->id,
                'email' => $client->email,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send activation email', [
                'client_id' => $client->id,
                'email' => $client->email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send order ready notification email when order status becomes Order Ready
     */
    public function sendOrderReadyNotification(Order $order): bool
    {
        $order->loadMissing(['client', 'store', 'items.menu']);

        $client = $order->client;
        if (!$client || empty($client->email)) {
            Log::info('Skipping order ready email: no client email', ['order_id' => $order->id]);
            return false;
        }

        try {
            Mail::to($client->email)->send(new OrderReadyNotificationMail($order));

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_READY,
                'note' => 'Notifikasi email Menu Ready berhasil dikirim ke ' . $client->email,
            ]);

            Log::info('Order ready email notification sent successfully', [
                'order_number' => $order->order_number,
                'client_email' => $client->email,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send order ready email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
