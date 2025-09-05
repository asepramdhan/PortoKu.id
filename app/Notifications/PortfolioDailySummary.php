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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $change = number_format($this->percentageChange, 2);
        $value = number_format($this->currentValue, 0, ',', '.');
        $greeting = "Halo, " . $notifiable->name . "!"; // Mengambil nama user

        $subject = "Ringkasan Harian Portofolio Aset Anda";
        $line = "Portofolio aset Anda hari ini bernilai **Rp " . $value . "**. ";

        if ($this->percentageChange > 0) {
            $line .= "Ini adalah kenaikan sebesar **" . $change . "%** dari kemarin. Kerja bagus!";
            $subject .= " 📈"; // Tambah emoji naik
        } elseif ($this->percentageChange < 0) {
            $line .= "Ini adalah penurunan sebesar **" . abs($change) . "%** dari kemarin. Tetap semangat!";
            $subject .= " 📉"; // Tambah emoji turun
        } else {
            $line .= "Nilainya tidak berubah dari kemarin.";
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line)
            ->action('Lihat Portofolio Anda', url('/portofolio')) // Arahkan ke halaman portofolio
            ->line('Terima kasih telah menggunakan PortoKu.id!');
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
