<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MeetingFileController extends Controller
{
    /**
     * Sirve el audio de un segmento (con soporte de Range para que el
     * reproductor pueda buscar/adelantar sin descargar todo el archivo).
     */
    public function audio(Meeting $meeting, int $segmento = 0): BinaryFileResponse
    {
        $path = $meeting->archivosAudio()[$segmento] ?? null;

        abort_unless($path && is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'audio/wav',
        ]);
    }
}
