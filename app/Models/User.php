<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * El ID no es autoincremental porque viene de la App Madre
     */
    public $incrementing = false;
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'username',
        'name',
        'password',
        'avatar',
        'puesto',
        'roles_list',
        'permisos_list',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atributos virtuales para compatibilidad con el Frontend
     */
    protected $appends = ['roles', 'permissions', 'permisos'];

    public function getRolesAttribute()
    {
        return $this->getAttribute('roles_list') ?? [];
    }

    public function getPermissionsAttribute()
    {
        return $this->getAttribute('permisos_list') ?? [];
    }

    public function getPermisosAttribute()
    {
        return $this->getAttribute('permisos_list') ?? [];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles_list' => 'array',
            'permisos_list' => 'array',
        ];
    }
}
