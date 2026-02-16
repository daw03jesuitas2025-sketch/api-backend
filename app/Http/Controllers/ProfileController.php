<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProfileController extends Controller
{
    /**
     * SHOW (Antes edit): Muestra los datos del perfil del usuario.
     * En una API no devolvemos la vista del formulario ('view'), sino los datos directos (JSON).
     * Nota: Esto hace lo mismo que la función 'me()' del AuthController.
     */
    public function show(Request $request)
    {
        try {
            return response()->json($request->user(), 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al cargar perfil'], 500);
        }
    }

    /**
     * UPDATE: Actualiza la información del perfil (Nombre y Email).
     * Hemos quitado la redirección y ahora devolvemos el usuario actualizado en JSON.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // 1. Validar datos
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            // El email debe ser único, pero ignorando el ID del propio usuario actual
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // 2. Si cambia el email, invalidamos la fecha de verificación (si usas verificación)
            if ($user->email !== $request->email) {
                $user->email_verified_at = null;
            }

            // 3. Guardar cambios
            $user->fill($request->only(['name', 'email']));
            $user->save();

            return response()->json([
                'message' => 'Perfil actualizado correctamente',
                'user' => $user
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al actualizar perfil', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DESTROY: Elimina la cuenta del usuario.
     * En API no invalidamos sesión (porque no hay sesión), pero sí revocamos el token.
     * Requerimos la contraseña actual para seguridad.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $user = $request->user();

            // 1. Verificar que la contraseña enviada es correcta
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Contraseña incorrecta'], 403);
            }

            // 2. Eliminar usuario
            $user->delete();

            // 3. Opcional: Cerrar sesión (invalidar token JWT actual)
            // Auth::logout();

            return response()->json(['message' => 'Cuenta eliminada correctamente'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al eliminar cuenta', 'message' => $e->getMessage()], 500);
        }
    }
}
