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

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                // Filtramos por los campos más relevantes
                $q->where('codigo_activo', 'like', "%{$search}%")
                  ->orWhere('numero_serie', 'like', "%{$search}%")
                  ->orWhere('nombre_equipo', 'like', "%{$search}%")
                  ->orWhere('usuario_equipo', 'like', "%{$search}%")
                  ->orWhere('nombre_responsable', 'like', "%{$search}%");
            });
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
            'codigo_activo' => 'required|string', // Se quitó unique
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
            // Se quitó unique
            'codigo_activo' => 'sometimes|string',
        ]);

        $inventario->update($request->all());

        return response()->json($inventario);
    }

    public function destroy(string $id)
    {
        Inventario::destroy($id);
        return response()->json(null, 204);
    }

    public function export()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventario.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // Encabezados del CSV
            fputcsv($handle, [
                'ID',
                'Agencia',
                'Categoria',
                'Codigo Activo',
                'Numero Serie',
                'Area',
                'Responsable',
                'Puesto Resp.',
                'Usuario Equipo',
                'Tipo Dispositivo',
                'Activo',
                'Marca',
                'Modelo',
                'Nombre Equipo',
                'IP Address',
                'Procesador',
                'RAM',
                'Almacenamiento',
                'SO',
                'Licencia SO',
                'Office',
                'Licencia Office',
                'Antivirus',
                'Observaciones',
                'Fecha Creacion'
            ]);

            // Procesar en chunks para no saturar memoria RAM
            Inventario::with(['agencia', 'categoria'])->chunk(100, function ($inventarios) use ($handle) {
                foreach ($inventarios as $item) {
                    fputcsv($handle, [
                        $item->id,
                        $item->agencia ? $item->agencia->nombre : 'N/A', // Corregido: nombre en vez de name
                        $item->categoria ? $item->categoria->nombre : 'N/A', // Corregido: nombre en vez de name
                        $item->codigo_activo,
                        $item->numero_serie,
                        $item->area,
                        $item->nombre_responsable,
                        $item->puesto_responsable,
                        $item->usuario_equipo,
                        $item->tipo_dispositivo,
                        $item->activo,
                        $item->marca,
                        $item->modelo,
                        $item->nombre_equipo,
                        $item->ip_address,
                        $item->procesador,
                        $item->memoria_ram,
                        $item->almacenamiento,
                        $item->sistema_operativo,
                        $item->licencia_so,
                        $item->version_office,
                        $item->licencia_office,
                        $item->antivirus,
                        $item->observaciones,
                        $item->created_at->toDateTimeString()
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
