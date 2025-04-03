<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var TravelRequest
     */
    protected $travelRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(TravelRequest $travelRequest)
    {
        $this->travelRequest = $travelRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusText = $this->getStatusText();
        
        $mailMessage = (new MailMessage)
                    ->subject("Solicitação de Viagem - {$statusText}")
                    ->greeting("Olá {$notifiable->name},")
                    ->line("Sua solicitação de viagem para {$this->travelRequest->destination} de {$this->travelRequest->departure_date->format('d/m/Y')} a {$this->travelRequest->return_date->format('d/m/Y')} foi {$statusText}.")
                    ->line("Status atual: {$this->travelRequest->status}");
        
        if ($this->travelRequest->status === 'cancelado' && $this->travelRequest->reason_for_cancellation) {
            $mailMessage->line("Motivo do cancelamento: {$this->travelRequest->reason_for_cancellation}");
        }
                    
        return $mailMessage->action('Ver Detalhes', url('/'))
                    ->line('Obrigado por usar nosso sistema de viagens corporativas!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'travel_request_id' => $this->travelRequest->id,
            'status' => $this->travelRequest->status,
            'destination' => $this->travelRequest->destination,
            'departure_date' => $this->travelRequest->departure_date->format('Y-m-d'),
            'return_date' => $this->travelRequest->return_date->format('Y-m-d'),
            'reason_for_cancellation' => $this->travelRequest->reason_for_cancellation,
        ];
    }
    
    /**
     * Get descriptive status text
     */
    protected function getStatusText(): string
    {
        return match($this->travelRequest->status) {
            'aprovado' => 'aprovada',
            'cancelado' => 'cancelada',
            default => $this->travelRequest->status
        };
    }
}
