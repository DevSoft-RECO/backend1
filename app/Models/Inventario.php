<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $fillable = [
        'agencia_id',
        'categoria_id',
        'codigo_activo',
        'numero_serie',
        'area',
        'nombre_responsable',
        'puesto_responsable',
        'usuario_equipo',
        'password_equipo',
        'tipo_dispositivo',
        'activo',
        'marca',
        'modelo',
        'nombre_equipo',
        'ip_address',
        'procesador',
        'memoria_ram',
        'almacenamiento',
        'sistema_operativo',
        'licencia_so',
        'version_office',
        'licencia_office',
        'antivirus',
        'bloqueo_usb',
        'es_remoto',
        'observaciones'
    ];

    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function incidentes()
    {
        return $this->hasMany(Incidente::class);
    }
}
