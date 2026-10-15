<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El texto que trae Laravel por defecto para este mail está en
        // inglés — el resto del sistema está en español, así que se
        // reemplaza acá (único lugar) en vez de publicar/traducir todo el
        // paquete de notificaciones.
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('Recuperar contraseña - '.config('app.name'))
                ->greeting('¡Hola!')
                ->line('Recibimos un pedido para restablecer la contraseña de tu cuenta.')
                ->action('Poner una contraseña nueva', $url)
                ->line('Este link vence en 60 minutos.')
                ->line('Si no pediste esto, podés ignorar este mail — tu contraseña sigue igual.');
        });
    }
}
