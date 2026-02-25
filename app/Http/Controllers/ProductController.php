<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    
    public function index(): JsonResponse
    {
        $products = Product::all();

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo'              => 'required|string|max:50|unique:products,codigo',
            'nombre'              => 'required|string|max:255',
            'precio'              => 'required|numeric|min:0',
            'porcentaje_impuesto' => 'required|numeric|min:0|max:100',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Producto creado exitosamente.',
            'data'    => $product,
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $product,
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'codigo'              => 'sometimes|required|string|max:50|unique:products,codigo,' . $product->id,
            'nombre'              => 'sometimes|required|string|max:255',
            'precio'              => 'sometimes|required|numeric|min:0',
            'porcentaje_impuesto' => 'sometimes|required|numeric|min:0|max:100',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado exitosamente.',
            'data'    => $product->fresh(),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado exitosamente.',
        ]);
    }
}
