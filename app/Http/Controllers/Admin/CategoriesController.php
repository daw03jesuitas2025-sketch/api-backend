<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class CategoriesController extends Controller
{
    /**
     * INDEX: Listar todas las categorías.
     **/
    public function index()
    {
        try {
            $categories = Category::orderBy('name', 'asc')->get();
            return response()->json($categories, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al listar categorías'], 500);
        }
    }

    /**
     * SHOW: Mostrar una sola categoría (por si la necesitas en el futuro).
     */
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json($category, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
    }

    /**
     * STORE: Crear nueva categoría.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:categories,name',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $category = Category::create([
                'name' => $request->name
            ]);

            return response()->json([
                'message' => 'Categoría creada correctamente',
                'data' => $category
            ], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al crear categoría'], 500);
        }
    }

    /**
     * UPDATE: Editar nombre de categoría.
     * (Solo ADMIN).
     */
    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255|unique:categories,name,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $category->update([
                'name' => $request->name
            ]);

            return response()->json([
                'message' => 'Categoría actualizada',
                'data' => $category
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al actualizar categoría'], 500);
        }
    }

    /**
     * DESTROY: Eliminar categoría.
     * (Solo ADMIN).
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json(['message' => 'Categoría eliminada correctamente'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al eliminar categoría'], 500);
        }
    }
}
