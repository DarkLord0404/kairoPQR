<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvisoEliminacionAudio extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Meeting $meeting)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Se eliminará el audio de: '.$this->meeting->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.aviso-eliminacion-audio',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
