<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    /**
     * Login y generación del token JWT
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // 🔥 IMPORTANTE: devolvemos token + user (Angular lo agradece)
        return $this->respondWithToken($token);
    }

    /**
     * Registro de usuario
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Usuario autenticado
     */
    public function me()
    {
        return response()->json(JWTAuth::user());
    }

    /**
     * Logout (invalida el token)
     */
    public function logout()
    {
        try {
            $token = JWTAuth::getToken();

            if ($token) {
                JWTAuth::invalidate($token);
            }

            return response()->json([
                'message' => 'Sesión cerrada correctamente'
            ]);

        } catch (JWTException $e) {
            // Si el token ya es inválido, para el front igualmente es logout OK
            return response()->json([
                'message' => 'Sesión cerrada'
            ], 200);
        }
    }

    /**
     * Refrescar token
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'message' => 'Token no enviado'
                ], 401);
            }

            $newToken = JWTAuth::refresh($token);

            return $this->respondWithToken($newToken);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token expirado'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token inválido'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'message' => 'No se pudo refrescar el token'
            ], 401);
        }
    }

    /**
     * Respuesta estándar con token
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,

            // ✅ útil para Angular (para navbar, profile, etc.)
            'user' => JWTAuth::user()
        ]);
    }
}
