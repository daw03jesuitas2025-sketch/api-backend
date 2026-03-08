<?php

namespace App\Http\Controllers;

use App\Models\Petition;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PetitionController extends Controller
{
    use AuthorizesRequests;

    private function sendResponse($data, $message, $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    private function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    public function index()
    {
        try {
            $petitions = Petition::with(['user', 'category', 'files'])
                ->orderBy('id', 'desc')
                ->get();

            return $this->sendResponse($petitions, 'Peticiones recuperadas con éxito');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar peticiones', $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $petition = Petition::with(['user', 'category', 'files', 'signedUsers'])->findOrFail($id);

            $has_signed = false;
            if ($user = Auth::guard('api')->user()) {
                $has_signed = $petition->signedUsers()->where('user_id', $user->id)->exists();
            }

            $petition->has_signed = $has_signed;

            return $this->sendResponse($petition, 'Petición encontrada');
        } catch (\Exception $e) {
            return $this->sendError('Petición no encontrada', [], 404);
        }
    }

    public function mine()
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return $this->sendError('No autenticado', [], 401);
            }

            $petitions = Petition::where('user_id', $user->id)
                ->with(['user', 'category', 'files'])
                ->orderBy('id', 'desc')
                ->get();

            return $this->sendResponse($petitions, 'Tus peticiones recuperadas con éxito');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar tus peticiones', $e->getMessage(), 500);
        }
    }

    public function signed()
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return $this->sendError('No autenticado', [], 401);
            }

            $petitions = $user->signedPetitions()

                ->with(['user', 'category', 'files'])
                ->orderBy('id', 'desc')
                ->get();

            return $this->sendResponse($petitions, 'Peticiones firmadas recuperadas con éxito');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar peticiones firmadas', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        // 1. Validar archivos como array
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'destinatary' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'files' => 'nullable|array',
            'files.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096', // valida cada imagen individualmente
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        try {
            // 2. Crear la petición principal
            $petition = new Petition();
            $petition->title = $request->title;
            $petition->description = $request->description;
            $petition->destinatary = $request->destinatary;
            $petition->category_id = $request->category_id;
            $petition->user_id = Auth::guard('api')->id();
            $petition->signeds = 0;
            $petition->status = 'pending';
            $petition->save();

            // 3. Procesar múltiples archivos en bucle
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    // Guardar archivo en storage/app/public/peticiones
                    $path = $file->store('peticiones', 'public');

                    // Crear registro en la tabla Files relacionada
                    $petition->files()->create([
                        'name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                    ]);
                }
            }
            return $this->sendResponse(
                $petition->load(['files', 'category', 'user']),
                'Petición creada con éxito',
                201
            );
        } catch (\Exception $e) {
            return $this->sendError('Error al crear la petición', $e->getMessage(), 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $petition = Petition::findOrFail($id);

            // Validación del usuario
            $user = Auth::guard('api')->user();
            if (!$user || $petition->user_id !== $user->id) {
                return $this->sendError('No autorizado', [], 403);
            }

            // 1. Validar los nuevos campos y el array de archivos
            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
                'destinatary' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'files'       => 'nullable|array',
                'files.*'     => 'image|mimes:jpeg,png,jpg,webp|max:4096',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Error de validación', $validator->errors(), 422);
            }

            // 2. Actualizar datos básicos
            $petition->update($request->only(['title', 'description', 'destinatary', 'category_id']));

            // 3. Procesar múltiples imágenes si se han enviado nuevas
            if ($request->hasFile('files')) {
                // Guardamos cada archivo nuevo
                foreach ($request->file('files') as $file) {
                    $path = $file->store('peticiones', 'public');

                $petition->files()->create([
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path
                ]);
            }
            }
            return $this->sendResponse(
                $petition->load('files'),
                'Petición actualizada con éxito'
            );
    } catch (\Exception $e) {
            return $this->sendError('Error al actualizar', $e->getMessage(), 500);
        }
    }
    public function destroy(Request $request, $id)
    {
        try {
            $petition = Petition::with(['files', 'signedUsers'])->findOrFail($id);

            $user = Auth::guard('api')->user();
            if (!$user) {
                return $this->sendError('No autenticado', [], 401);
            }

            if ($petition->user_id !== $user->id) {
                return $this->sendError('No autorizado', [], 403);
            }

            $petition->signedUsers()->detach();

            foreach ($petition->files as $file) {
                if ($file->file_path) {
                    Storage::disk('public')->delete($file->file_path);
                }
            }

            $petition->files()->delete();

            $petition->delete();

            return $this->sendResponse(null, 'Petición eliminada con éxito');

        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar', $e->getMessage(), 500);
        }
    }

    public function firmar(Request $request, $id)
    {
        try {
            $petition = Petition::findOrFail($id);
            $user = Auth::guard('api')->user();

            if (!$user) {
                return $this->sendError('No autenticado', [], 401);
            }

            if ($petition->signedUsers()->where('user_id', $user->id)->exists()) {
                return $this->sendError('Ya has firmado esta petición', [], 403);
            }

            $petition->signedUsers()->attach($user->id);
            $petition->increment('signeds');

            return $this->sendResponse($petition, 'Petición firmada con éxito', 201);
        } catch (\Exception $e) {
            return $this->sendError('No se pudo firmar la petición', $e->getMessage(), 500);
        }
    }
}
