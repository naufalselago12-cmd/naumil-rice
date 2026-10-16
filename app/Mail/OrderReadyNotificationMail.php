<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReadyNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $traceUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->loadMissing(['store', 'client', 'items']);
        $this->traceUrl = url('/trace/' . $order->order_number);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🍽️ Pesanan Siap / Ready! #' . $this->order->order_number . ' - ' . ($this->order->store->name ?? 'Naumil Rice'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order_ready',
            with: [
                'order' => $this->order,
                'traceUrl' => $this->traceUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
