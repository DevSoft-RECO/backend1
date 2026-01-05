<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventario::with(['agencia', 'categoria']);

        if ($request->filled('agencia_id')) {
            $query->where('agencia_id', $request->agencia_id);
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Paginación de 20 registros por defecto
        return $query->paginate($request->input('per_page', 20));
    }

    public function store(Request $request)
    {
        // Logica simplificada: aceptamos nullables según definió el usuario
        // Validamos solo lo crítico (foreign keys y unique)
        $validated = $request->validate([
            'agencia_id' => 'required|exists:agencias,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'codigo_activo' => 'required|string|unique:inventarios,codigo_activo',
            // Resto de campos son nullables en BD, Laravel los pasa si están en request
        ]);

        // Combinamos request all para capturar los campos no validados explícitamente pero fillable
        $data = array_merge($request->all(), $validated);

        $inventario = Inventario::create($data);

        return response()->json($inventario, 201);
    }

    public function show(string $id)
    {
        return Inventario::with(['agencia', 'categoria', 'incidentes'])->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $inventario = Inventario::findOrFail($id);

        $validated = $request->validate([
            'agencia_id' => 'sometimes|exists:agencias,id',
            'categoria_id' => 'sometimes|nullable|exists:categorias,id',
            // Unique ignora el ID actual al actualizar
            'codigo_activo' => 'sometimes|string|unique:inventarios,codigo_activo,' . $id,
        ]);

        $inventario->update($request->all());

        return response()->json($inventario);
    }

    public function destroy(string $id)
    {
        Inventario::destroy($id);
        return response()->json(null, 204);
    }
}
