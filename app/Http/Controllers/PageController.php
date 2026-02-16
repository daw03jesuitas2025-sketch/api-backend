<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Petition;
use App\Models\Category;
use Exception;

class PageController extends Controller
{
    public function home()
    {
        try {
            $petitions = Petition::with(['user', 'category', 'file'])
                ->orderBy('created_at', 'desc')
                ->take(4)
                ->get();

            $categories = Category::all();

            return response()->json([
                'petitions' => $petitions,
                'categories' => $categories
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
