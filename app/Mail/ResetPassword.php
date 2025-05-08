<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        return $this
            ->subject("Redefinição de senha - " . config('app.name'))
            ->view('emails.reset-password')
            ->with([
                'name' => $this->user->name,
                'resetLink' => route('password.reset', ['token' => $this->token, 'email' => $this->user->email]),
            ]);
    }
}