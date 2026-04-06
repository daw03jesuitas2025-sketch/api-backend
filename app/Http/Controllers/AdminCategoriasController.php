<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class AdminCategoriasController extends Controller
{
    public function index()
    {
        $categorias = Category::withCount('petitions')->orderBy('id')->get();
        return response()->json(['success' => true, 'data' => $categorias]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name'
        ]);
        $categoria = Category::create($data);

        // Recargamos con el count para que el frontend lo tenga
        $categoria->loadCount('petitions');

        return response()->json(['success' => true, 'data' => $categoria], 201);
    }

    public function update(Request $request, $id)
    {
        $categoria = Category::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id
        ]);
        $categoria->update($data);
        return response()->json(['success' => true, 'data' => $categoria]);
    }

    public function destroy($id)
    {
        $categoria = Category::withCount('petitions')->findOrFail($id);
        if ($categoria->petitions_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: la categoría tiene peticiones asociadas.'
            ], 403);
        }
        $categoria->delete();
        return response()->json(['success' => true, 'message' => 'Categoría eliminada correctamente.']);
    }
}
