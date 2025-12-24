<?php

namespace App\Http\Controllers;

use App\Models\Incidente;
use Illuminate\Http\Request;

class IncidenteController extends Controller
{
    public function index()
    {
        return Incidente::with('inventario')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventario_id' => 'required|exists:inventarios,id',
            'tipo' => 'required|string',
            'descripcion' => 'required|string',
            'fecha_reporte' => 'nullable|date',
        ]);

        $incidente = Incidente::create($validated);

        return response()->json($incidente, 201);
    }

    public function show(string $id)
    {
        return Incidente::with('inventario')->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $incidente = Incidente::findOrFail($id);

        $validated = $request->validate([
            'tipo' => 'sometimes|required|string',
            'descripcion' => 'sometimes|required|string',
            'fecha_reporte' => 'nullable|date',
        ]);

        $incidente->update($validated);

        return response()->json($incidente);
    }

    public function destroy(string $id)
    {
        Incidente::destroy($id);
        return response()->json(null, 204);
    }

    // Método personalizado para obtener incidentes de un inventario
    public function byInventario($inventarioId)
    {
        return Incidente::where('inventario_id', $inventarioId)
            ->orderBy('fecha_reporte', 'desc')
            ->get();
    }
}
