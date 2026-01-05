<?php

namespace App\Models\Eventos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'text_color',
        'is_active',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
