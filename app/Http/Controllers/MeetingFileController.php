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
        $dir = config('kairomeet.salidas_path');
        $base = $dir.'/'.$meeting->base_path;

        $path = $meeting->num_segmentos > 1
            ? sprintf('%s_part%03d.wav', $base, $segmento)
            : $base.'.wav';

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'audio/wav',
        ]);
    }
}
