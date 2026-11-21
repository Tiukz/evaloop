<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class WorkspaceImage
{
    public const RULES = ['bail', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'];

    private const MIME = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    /** The persistence callback returns the saved model and the image path it replaced. */
    public function save(?UploadedFile $file, string $directory, string $field, Closure $persist): mixed
    {
        if ($file === null) {
            return DB::transaction(fn () => $persist(null)[0]);
        }

        $disk = Storage::disk('local');
        $path = $disk->putFile($directory, $file, 'private');
        if ($path === false) {
            throw ValidationException::withMessages([$field => 'Gambar gagal disimpan. Pilih file dan coba lagi.']);
        }

        try {
            [$model, $previous] = DB::transaction(fn () => $persist($path));
        } catch (Throwable $exception) {
            $this->delete($path);

            throw $exception;
        }

        if ($previous) {
            $this->delete($previous);
        }

        return $model;
    }

    public function response(?string $path): BinaryFileResponse
    {
        $type = self::MIME[pathinfo($path ?? '', PATHINFO_EXTENSION)] ?? null;
        $disk = Storage::disk('local');
        abort_unless($path && $type && $disk->exists($path), 404);

        return new BinaryFileResponse($disk->path($path), 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ], public: false);
    }

    private function delete(string $path): void
    {
        if (! Storage::disk('local')->delete($path)) {
            report(new RuntimeException('Failed to remove a workspace image from private storage.'));
        }
    }
}
