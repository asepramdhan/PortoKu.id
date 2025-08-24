<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortfolioDailySummary extends Notification
{
    use Queueable;

    public float $percentageChange;
    public float $currentValue;

    /**
     * Create a new notification instance.
     */
    public function __construct(float $percentageChange, float $currentValue)
    {
        $this->percentageChange = $percentageChange;
        $this->currentValue = $currentValue;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database']; // Simpan notifikasi ke database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'percentage_change' => $this->percentageChange,
            'current_value' => $this->currentValue,
        ];
    }
}
