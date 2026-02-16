<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\File;
use App\Models\Petition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class PetitionsController extends Controller
{
    /**
     * INDEX: Listar todas las peticiones (Vista de Administrador).
     * Devuelve JSON con las relaciones (archivo, categoría, autor).
     */
    public function index()
    {
        try {
            $petitions = Petition::with(['file', 'category', 'user'])
                ->orderBy('id', 'asc')
                ->get();

            return response()->json($petitions, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al listar peticiones'], 500);
        }
    }

    /**
     * SHOW: Ver detalle de una petición (Para editarla).
     * (Reemplaza a la función edit() que devolvía la vista).
     */
    public function show($id)
    {
        try {
            $petition = Petition::with(['file', 'category', 'user'])->findOrFail($id);
            return response()->json($petition, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Petición no encontrada'], 404);
        }
    }

    /**
     * STORE: Crear una petición desde el panel de Admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "title"       => "required|max:255",
            "description" => "required",
            "category_id" => "required|exists:categories,id",
            "file"        => "required|file|image|mimes:jpeg,webp,png,jpg,gif,svg|max:2048",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Crear objeto (Mapeando a inglés)
            $petition = new Petition();
            $petition->title       = $request->title;
            $petition->description = $request->description;
            $petition->category_id = $request->category_id;
            $petition->user_id     = Auth::id();
            $petition->signeds     = 0;
            $petition->status      = 'pending';
            $petition->destinatary = "everyone"; // Valor por defecto según tu código original

            $petition->save();

            // Subir archivo
            if ($request->hasFile('file')) {
                $this->fileUpload($request, $petition->id);
            }

            return response()->json(['message' => 'Petición creada correctamente', 'data' => $petition], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al crear la petición', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * UPDATE: Actualizar petición.
     * Aquí el Admin puede cambiar el estado (status).
     */
    public function update(Request $request, $id)
    {
        try {
            $petition = Petition::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title'       => 'required|max:255',
                'description' => 'required',
                'category_id' => 'required|exists:categories,id',
                'status'      => 'required', // Importante para el admin
                'file'        => 'nullable|file|image|mimes:jpeg,webp,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Actualizar campos
            $petition->title       = $request->title;
            $petition->description = $request->description;
            $petition->category_id = $request->category_id;
            $petition->status      = $request->status;

            // Gestión de archivo (Si viene uno nuevo, borrar el viejo)
            if ($request->hasFile('file')) {
                if ($petition->file) {
                    @unlink(public_path($petition->file->file_path)); // Borrado físico
                    $petition->file->delete(); // Borrado en DB
                }
                $this->fileUpload($request, $petition->id);
            }

            $petition->save();

            return response()->json(['message' => 'Petición actualizada correctamente', 'data' => $petition], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al actualizar'], 500);
        }
    }

    /**
     * DESTROY (Antes delete): Eliminar petición.
     * CONTIENE LÓGICA DE SEGURIDAD: No borra si hay firmas.
     */
    public function destroy($id)
    {
        try {
            $petition = Petition::findOrFail($id);

            // 1. COMPROBACIÓN: Si tiene firmas, prohibido borrar.
            if ($petition->signeds > 0) {
                return response()->json([
                    'error' => 'No puedes eliminar esta petición porque ya tiene ' . $petition->signeds . ' firmas.'
                ], 403); // 403 = Forbidden
            }

            // 2. Si no tiene firmas, borrar imagen física
            if ($petition->file) {
                @unlink(public_path('petitions/' . $petition->file->name)); // Ojo: name o file_path según guardes
                $petition->file->delete();
            }

            // 3. Borrar registro
            $petition->delete();

            return response()->json(['message' => 'Petición eliminada correctamente'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al eliminar'], 500);
        }
    }

    /**
     * Helper para subir archivos (Limpiado y simplificado)
     */
    private function fileUpload(Request $request, $petition_id)
    {
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();

        // Mover a la carpeta public/petitions
        $file->move(public_path('petitions'), $filename);

        // Crear registro en base de datos
        $fileModel = new File;
        $fileModel->petition_id = $petition_id;
        $fileModel->name = $filename;
        $fileModel->file_path = 'petitions/' . $filename; // Guardamos ruta relativa
        $fileModel->save();

        return $fileModel;
    }
}
