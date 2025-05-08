<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountActivation extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this
            ->subject("Ative sua conta em " . config('app.name'))
            ->view('emails.activate-account')
            ->with([
                'name' => $this->user->name,
                'activationLink' => route('activation.activate', ['token' => $this->user->activation_token]),
            ]);
    }
}
