<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class AdminUsersController extends Controller
{
    /**
     * INDEX: Listar todos los usuarios.
     */
    public function index()
    {
        try {
            $users = User::orderBy('id', 'asc')->get();
            return response()->json($users, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al listar usuarios'], 500);
        }
    }

    /**
     * SHOW: Ver un usuario específico.
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            return response()->json($user, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
    }

    /**
     * STORE: Crear nuevo usuario (Con rol).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id'  => 'required|integer' // <--- RESTAURADO
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'role_id'  => $request->role_id, // <--- RESTAURADO
                'password' => bcrypt($request->password),
            ]);

            return response()->json(['message' => 'Usuario creado correctamente', 'data' => $user], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al crear usuario'], 500);
        }
    }

    /**
     * UPDATE: Actualizar usuario (Con rol).
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
                'role_id'  => 'required|integer', // <--- RESTAURADO
                'password' => 'nullable|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Datos básicos a actualizar
            $data = [
                'name'    => $request->name,
                'email'   => $request->email,
                'role_id' => $request->role_id // <--- RESTAURADO
            ];

            // Solo actualizamos la contraseña si el admin escribió algo nuevo
            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
            }

            $user->update($data);

            return response()->json(['message' => 'Usuario actualizado correctamente', 'data' => $user], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al actualizar usuario'], 500);
        }
    }

    /**
     * DESTROY: Eliminar usuario.
     * Mantiene las protecciones de seguridad.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // 1. No puedes eliminar tu propia cuenta
            if (Auth::id() == $user->id) {
                return response()->json(['error' => 'No puedes eliminar tu propia cuenta.'], 403);
            }

            // 2. No se puede eliminar si ha creado peticiones
            if ($user->petitions()->exists()) {
                return response()->json(['error' => 'No se puede eliminar el usuario porque ha creado peticiones.'], 403);
            }

            $user->delete();

            return response()->json(['message' => 'Usuario eliminado correctamente'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al eliminar usuario'], 500);
        }
    }
}
