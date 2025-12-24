<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidente extends Model
{
    protected $fillable = [
        'inventario_id',
        'tipo',
        'descripcion',
        'fecha_reporte'
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }
}
