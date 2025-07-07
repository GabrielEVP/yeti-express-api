<?php

namespace App\Auth\Controllers;

use App\Auth\Requests\AuthRequest;
use App\Auth\Services\AuthService;
use App\Core\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private AuthService $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function register(AuthRequest $request): JsonResponse
    {
        $result = $this->service->register($request->validated());
        return response()->json($result, 200);
    }

    public function login(AuthRequest $request): JsonResponse
    {
        $result = $this->service->login($request->validated());
        return response()->json($result, 200);
    }

    public function changePassword(AuthRequest $request): JsonResponse
    {
        $result = $this->service->changePassword(auth()->id());
        return response()->json($result, 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . auth()->id()],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto.',
            'name.max' => 'El nombre no debe superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.max' => 'El correo electrónico no debe superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está en uso.',
        ]);

        $result = $this->service->updateProfile(auth()->id(), $validated);
        return response()->json($result, 200);
    }

    public function logout(): JsonResponse
    {
        $this->service->logout(auth()->id());

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ], 200);
    }
}
