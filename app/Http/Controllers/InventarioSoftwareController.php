<?php

namespace App\Http\Controllers;

use App\Models\InventarioSoftware;
use Illuminate\Http\Request;

class InventarioSoftwareController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = InventarioSoftware::query();

        // Filtro opcional por nombre (uso de índice)
        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        // Filtro opcional por tipo (uso de índice)
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // Paginación para no sobrecargar el frontend
        return $query->paginate($request->input('per_page', 20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'enlace' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|string|max:255',
            'usuario' => 'nullable|string|max:255', // Alfanumérico según requerimiento, pero string abarca eso
            'clave' => 'nullable|string|max:255',
        ]);

        $software = InventarioSoftware::create($validated);

        return response()->json($software, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return InventarioSoftware::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $software = InventarioSoftware::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'enlace' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'sometimes|required|string|max:255',
            'usuario' => 'nullable|string|max:255',
            'clave' => 'nullable|string|max:255',
        ]);

        $software->update($validated);

        return response()->json($software);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        InventarioSoftware::destroy($id);
        return response()->json(null, 204);
    }
}
