<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class AccountActivation extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ative sua conta em " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activate-account',
            with: [
                'name' => $this->user->name,
                'activationLink' => route('activation.activate', ['token' => $this->user->activation_token]),
            ]
        );
    }

    public function attachments(): array
    {
        $logoPath = public_path('storage/logo-sem fundo.png');
        
        if (file_exists($logoPath)) {
            return [
                Attachment::fromPath($logoPath)
                    ->as('logo.png')
                    ->withMime('image/png')
            ];
        }
        
        return [];
    }
}