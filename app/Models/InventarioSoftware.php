<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioSoftware extends Model
{
    protected $table = 'inventario_software';

    protected $fillable = [
        'nombre',
        'enlace',
        'descripcion',
        'tipo',
        'usuario',
        'clave',
    ];
}
