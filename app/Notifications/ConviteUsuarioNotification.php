<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConviteUsuarioNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $convidadoPor) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'convite.aceitar',
            now()->addDays(7),
            ['usuario' => $notifiable->id],
        );

        return (new MailMessage)
            ->subject('Você foi convidado para a PsiAuto')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("{$this->convidadoPor->name} convidou você para acessar o painel da {$this->convidadoPor->empresa->nome_fantasia} na PsiAuto.")
            ->action('Aceitar convite', $url)
            ->line('Esse link expira em 7 dias.');
    }
}
