<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function session(Request $request): JsonResponse
    {
        return $this->respond(['csrf_token' => $request->session()->token()]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        abort_if($request->user('web'), 409);

        if (! Auth::guard('web')->attempt($request->validated())) {
            throw ValidationException::withMessages(['email' => 'Email atau password tidak sesuai.']);
        }

        $request->session()->regenerate();

        return $this->respond([
            'user' => $this->profile($request->user('web')),
            'csrf_token' => $request->session()->token(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->respond($this->profile($request->user('web')));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->respond([
            'user' => null,
            'csrf_token' => $request->session()->token(),
        ]);
    }

    private function profile(User $user): array
    {
        $program = $user->studyProgram;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'study_program' => $program ? ['id' => $program->id, 'name' => $program->name] : null,
        ];
    }

    private function respond(array $data): JsonResponse
    {
        return response()->json(['data' => $data])->header('Cache-Control', 'private, no-store');
    }
}
