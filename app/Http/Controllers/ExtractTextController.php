<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class ExtractTextController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:10240', 'mimes:txt,pdf'],
        ]);

        $file = $request->file('archivo');
        $mime = $file->getMimeType();

        if ($mime === 'application/pdf' || $file->getClientOriginalExtension() === 'pdf') {
            $tmp = $file->store('tmp_extracciones', 'local');
            $path = storage_path('app/'.$tmp);

            $result = Process::timeout(30)->run(['pdftotext', '-layout', $path, '-']);

            @unlink($path);

            if (!$result->successful() || trim($result->output()) === '') {
                return response()->json(['error' => 'No se pudo extraer texto del PDF. Verifique que no esté protegido o escaneado sin OCR.'], 422);
            }

            return response()->json(['texto' => trim($result->output())]);
        }

        // txt
        return response()->json(['texto' => trim($file->get())]);
    }
}
