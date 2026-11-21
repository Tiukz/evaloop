<?php

use Illuminate\Foundation\Application;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->prefix('api/v1')->name('mobile.')
                ->group(base_path('routes/mobile.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
        // Keep default Laravel session and CSRF middleware enabled.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) =>
            $request->is('api/v1', 'api/v1/*') || $request->expectsJson()
        );

        $exceptions->render(function (QueryException $exception, Request $request) {
            if (! $request->is('api/v1', 'api/v1/*') && ! $request->expectsJson()) {
                return response()->view('errors.database', [], 503);
            }

            return response()->json(['message' => 'Layanan data belum tersedia.'], 503);
        });

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();
            if (! $request->is('api/v1', 'api/v1/*') || $status < 400) {
                return $response;
            }

            $body = ['message' => match ($status) {
                401 => 'Sesi tidak aktif. Silakan masuk.',
                403 => 'Anda tidak memiliki akses.',
                404 => 'Data tidak ditemukan.',
                405 => 'Metode HTTP tidak didukung.',
                409 => 'Sesi sudah masuk. Keluar dahulu untuk mengganti akun.',
                419 => 'Token CSRF tidak valid. Ambil token sesi yang baru.',
                422 => 'Periksa kembali isian Anda.',
                429 => 'Terlalu banyak percobaan. Coba lagi nanti.',
                503 => 'Layanan data belum tersedia.',
                default => $status >= 500 ? 'Terjadi kesalahan pada server.' : 'Permintaan tidak dapat diproses.',
            }];
            if ($status === 422 && $response instanceof JsonResponse) {
                $body['errors'] = $response->getData(true)['errors'] ?? [];
            }

            $headers = Arr::except($response->headers->all(), ['content-type', 'content-length']);

            return response()->json($body, $status, $headers)->header('Cache-Control', 'private, no-store');
        });
    })->create();
