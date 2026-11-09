<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancelOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $otp;
    public array $bankDetails;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $otp, array $bankDetails = [])
    {
        $this->order = $order;
        $this->otp = $otp;
        $this->bankDetails = $bankDetails;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔐 Kode OTP Pembatalan & Refund Pesanan #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order_cancel_otp',
            with: [
                'order' => $this->order,
                'otp' => $this->otp,
                'bankDetails' => $this->bankDetails,
            ],
        );
    }
}

