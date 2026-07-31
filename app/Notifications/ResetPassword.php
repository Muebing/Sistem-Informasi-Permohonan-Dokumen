<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;

/**
 * Password reset notification that links to the client-side SPA
 * instead of a server-rendered "password.reset" route.
 */
class ResetPassword extends BaseResetPassword
{
    /**
     * Build the reset URL against the frontend app.
     *
     * @param  mixed  $notifiable
     */
    protected function resetUrl($notifiable): string
    {
        return rtrim((string) config('app.frontend_url'), '/')
            .'/#/reset-password?token='.$this->token
            .'&email='.urlencode((string) $notifiable->getEmailForPasswordReset());
    }
}
