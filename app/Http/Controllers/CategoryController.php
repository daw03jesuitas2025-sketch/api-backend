<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Category;


class CategoryController extends Controller {

    private function sendResponse($data, $message, $code = 200) {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    private function sendError($error, $errorMessages = [], $code = 404) {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if (!empty($errorMessages)) { $response['errors'] = $errorMessages; }
        return response()->json($response, $code);
    }

    public function index() {
        try {
            $categorias = Category::all();
            return $this->sendResponse($categorias, 'Categorias recuperadas con éxito');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar categorias', $e->getMessage(), 500);
        }
    }

}
