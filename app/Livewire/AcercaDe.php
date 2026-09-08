<?php

namespace App\Livewire;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AcercaDe extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
    }

    public function getContenidoHtmlProperty(): string
    {
        $markdown = file_get_contents(resource_path('markdown/arquitectura.md'));
        $converter = new GithubFlavoredMarkdownConverter(['html_input' => 'strip']);

        return (string) $converter->convert($markdown);
    }

    public function render()
    {
        return view('livewire.acerca-de');
    }
}
