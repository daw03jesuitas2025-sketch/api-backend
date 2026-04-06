<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Petition; // Asegúrate de que el nombre sea exacto

class AdminController extends Controller
{
    public function indexPeticiones()
    {
        $peticiones = Petition::with(['category', 'user', 'files'])
            ->withCount('signatures')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $peticiones
        ], 200);
    }
    public function showPeticion($id)
    {
        // Usamos findOrFail para que si el ID no existe devuelva 404 y no un error interno 500 o 422
        $peticion = Petition::with(['category', 'user', 'files'])
            ->withCount('signatures') // Añadimos esto para que la vista tenga el contador
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $peticion
        ], 200);
    }
    public function updatePeticion(Request $request, $id)
    {
        // 1. Buscamos la petición o lanzamos 404
        $petition = Petition::findOrFail($id);

        // 2. Validamos los datos entrantes
        // Nota: 'exists:categories,id' asegura que la categoría enviada sea válida en la BD
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'destinatary' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:pending,accepted',
        ]);

        // 3. Actualizamos los campos básicos (Mass Assignment)
        $petition->update($data);

        // 4. Gestión de Imágenes Nuevas (si existen)
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // Guardamos el archivo físicamente en storage/app/public/peticiones
                $path = $file->store('peticiones', 'public');

                // Creamos el registro en la tabla de archivos asociada a la petición
                // Esto asume que tienes la relación HasMany 'files' en tu modelo Petition
                $petition->files()->create([
                    'file_path' => $path,
                    'name'      => $file->getClientOriginalName()
                ]);
            }
        }

        // 5. Devolvemos la petición actualizada con sus relaciones para refrescar el Frontend
        return response()->json([
            'success' => true,
            'message' => 'Petition updated successfully by admin.',
            'data'    => $petition->load(['category', 'user', 'files'])
        ], 200);
    }
    public function destroyPeticion($id)
    {
        $peticion = Petition::findOrFail($id);
        $peticion->delete();
        return response()->json([
            'success' => true,
            'message' => 'Petition deleted successfully by admin.'
        ], 200);
    }
}
