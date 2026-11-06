<?php

namespace App\Notifications;

use App\Models\StorefrontOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class StorefrontOrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $storeName,
        private readonly string $reference,
        private readonly string $event,
        private readonly string $status,
        private readonly string $paymentStatus,
    ) {}

    public static function sendForOrder(StorefrontOrder $order, string $event): void
    {
        if (! is_string($order->customer_email) || $order->customer_email === '') {
            return;
        }

        NotificationFacade::route('mail', $order->customer_email)->notify(
            (new self(
                storeName: $order->tenant->name,
                reference: $order->reference_no,
                event: $event,
                status: $order->status,
                paymentStatus: $order->payment_status,
            )),
        );
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->greeting("Hello from {$this->storeName}")
            ->line("Order {$this->reference}: {$this->message()}")
            ->line('Keep your order reference when contacting the store.')
            ->line('Thank you for shopping with us.');
    }

    private function subject(): string
    {
        return match ($this->event) {
            'order_placed' => "Order {$this->reference} received",
            'payment_confirmed' => "Payment confirmed for order {$this->reference}",
            'order_cancelled' => "Order {$this->reference} cancelled",
            'delivery_tracking_updated' => "Delivery update for order {$this->reference}",
            default => "Update for order {$this->reference}",
        };
    }

    private function message(): string
    {
        return match ($this->event) {
            'order_placed' => 'Your order has been received and is pending review.',
            'payment_confirmed' => 'Your payment has been confirmed.',
            'order_cancelled' => 'Your order has been cancelled.',
            'delivery_tracking_updated' => 'Your delivery tracking details have been updated.',
            default => "The order status is now {$this->status} and payment status is {$this->paymentStatus}.",
        };
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
