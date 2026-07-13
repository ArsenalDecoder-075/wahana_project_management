<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $token;
    protected $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function toMail($notifiable)
    {
        $profileName = $notifiable->profile->name;
        $expireTime = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $this->email,
        ], false));

        return (new MailMessage)
            ->subject('Email Konfirmasi : Reset Password')
            ->markdown('mail.password_reset', [
                'profileName' => $profileName,
                'expireTime' => $expireTime,
                'resetUrl' => $resetUrl,
            ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */

    // public function toMail($notifiable)
    // {
    //     $profileName = $notifiable->profile->name;

    //     return (new MailMessage)
    //         ->subject('Email Konfirmasi : Reset Password')
    //         ->markdown('mail.password_reset', [
    //             'url' => $this->resetUrl,
    //             'profileName' => $profileName,

    //             You can add other data here as needed
    //         ]);
    //         ->line('Kami telah menerima permohonan anda untuk melakukan reset password')
    //         ->action('Reset Password', $this->resetUrl)
    //         ->line(Lang::get('Link reset password ini akan kadaluarsa dalam :count menit.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
    //         ->line('Jika Anda tidak melakukan permohonan untuk melakukan reset password, mohon abaikan email ini');
    // }


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
