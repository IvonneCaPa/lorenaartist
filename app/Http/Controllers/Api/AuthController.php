<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Autentica al usuario y emite un Bearer Token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Verificación de credenciales
        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Nombre descriptivo para el token (ej: navegador, app móvil, tablet del centro)
        $deviceName = $request->validated('device_name') ?? 'gestion-web';

        // Opcional: revocar tokens previos si solo se permite una sesión simultánea por usuario:
        // $user->tokens()->where('name', $deviceName)->delete();

        // Emisión del token Bearer
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 200);
    }

    /**
     * Revoca el token actual del usuario (Cierre de sesión).
     */
    public function logout(Request $request): JsonResponse
    {
        // Elimina únicamente el token con el que se realizó la petición actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    /**
     * Devuelve los datos del usuario autenticado actual.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ], 200);
    }
}