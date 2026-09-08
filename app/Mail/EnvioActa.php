<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class EnvioActa extends Mailable
{
    use Queueable, SerializesModels;

    public string $actaHtml;

    public function __construct(
        public Meeting $meeting,
        public string $acta,
    ) {
        $converter = new GithubFlavoredMarkdownConverter(['html_input' => 'strip']);
        $this->actaHtml = (string) $converter->convert($acta);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📋 Acta: '.$this->meeting->titulo.' ('.$this->meeting->fecha_inicio?->format('d/m/Y H:i').')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.envio-acta');
    }

    public function attachments(): array
    {
        return [];
    }
}
