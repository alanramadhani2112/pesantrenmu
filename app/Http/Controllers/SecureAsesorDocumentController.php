<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SecureAsesorDocumentController extends Controller
{
    public function __invoke(int $asesorId, string $field): BinaryFileResponse
    {
        if (! in_array($field, ['ktp_file', 'ijazah_file', 'kartu_nbm_file'], true)) {
            abort(404);
        }

        $asesor = Asesor::findOrFail($asesorId);
        $user = auth()->user();

        if ($asesor->user_id !== $user->id && ! $user->canAccessAdminArea()) {
            abort(403);
        }

        $path = $asesor->$field;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($path));
    }
}
