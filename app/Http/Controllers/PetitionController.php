<?php

namespace App\Http\Controllers;

use App\Models\Petition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PetitionController extends Controller
{
    public function index()
    {
        try {
            $petitions = Petition::with(['user', 'category', 'files'])
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($petitions, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al recuperar las peticiones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $petition = Petition::with(['user', 'category', 'files', 'signedUsers'])
                ->findOrFail($id);

            $petition->has_signed = false;

            if (Auth::guard('api')->check()) {
                $user = Auth::guard('api')->user();

                $petition->has_signed = $petition->signedUsers()
                    ->where('user_id', $user->id)
                    ->exists();
            }

            return response()->json($petition, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Petición no encontrada'], 404);
        }
    }

    public function mine()
    {
        try {
            $user = Auth::guard('api')->user();

            $petitions = Petition::where('user_id', $user->id)
                ->with(['category', 'files'])
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($petitions, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al recuperar tus peticiones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function signed()
    {
        try {
            $user = Auth::guard('api')->user();

            $petitions = $user->signedPetitions()
                ->with(['user', 'category', 'files'])
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($petitions, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al recuperar peticiones firmadas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|max:255',
            'description' => 'required',
            'destinatary' => 'required',
            'category_id' => 'required|exists:categories,id',
            'file'        => 'required|file|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $path = $file->store('peticiones', 'public');

            $petition = Petition::create([
                'title'       => $request->title,
                'description' => $request->description,
                'destinatary' => $request->destinatary,
                'category_id' => $request->category_id,
                'user_id'     => Auth::guard('api')->id(),
                'signeds'     => 0,
                'status'      => 'pending',
            ]);

            // ✅ OJO: files() porque es hasMany
            $petition->files()->create([
                'name'      => $file->getClientOriginalName(),
                'file_path' => $path,
            ]);

            return response()->json([
                'message' => 'Petición creada con éxito',
                'data' => $petition->load(['files', 'category', 'user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear la petición',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $petition = Petition::with('files')->findOrFail($id);

            if (Auth::guard('api')->id() !== $petition->user_id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $validator = Validator::make($request->all(), [
                'title'       => 'required|max:255',
                'description' => 'required',
                'destinatary' => 'required',
                'category_id' => 'required|exists:categories,id',
                'file'        => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $petition->update([
                'title'       => $request->title,
                'description' => $request->description,
                'destinatary' => $request->destinatary,
                'category_id' => $request->category_id,
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('peticiones', 'public');

                $fileRecord = $petition->files()->first();

                if ($fileRecord) {
                    Storage::disk('public')->delete($fileRecord->file_path);

                    $fileRecord->update([
                        'name'      => $file->getClientOriginalName(),
                        'file_path' => $path,
                    ]);
                } else {
                    $petition->files()->create([
                        'name'      => $file->getClientOriginalName(),
                        'file_path' => $path,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Petición actualizada correctamente',
                'data' => $petition->load(['files', 'category', 'user'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $petition = Petition::with('files')->findOrFail($id);

            if (Auth::guard('api')->id() !== $petition->user_id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $fileRecord = $petition->files()->first();

            if ($fileRecord) {
                Storage::disk('public')->delete($fileRecord->file_path);
                $fileRecord->delete();
            }

            $petition->delete();

            return response()->json(['message' => 'Petición eliminada correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function firmar($id)
    {
        try {
            $petition = Petition::findOrFail($id);
            $user = Auth::guard('api')->user();

            if ($petition->signedUsers()->where('user_id', $user->id)->exists()) {
                return response()->json(['error' => 'Ya has firmado esta petición'], 403);
            }

            $petition->signedUsers()->attach($user->id);
            $petition->increment('signeds');

            return response()->json(['message' => 'Petición firmada correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al firmar',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
