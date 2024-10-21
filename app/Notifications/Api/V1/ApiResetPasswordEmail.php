<?php

namespace App\Notifications\Api\V1;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiResetPasswordEmail extends ResetPassword
{
    use Queueable;

    /**a    nnb
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }


    protected function resetUrl($notifiable)

    {
        $email = $notifiable->getEmailForPasswordReset();
        return $email;
    }
}
