<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incidentes', function (Blueprint $table) {
            $table->id();
            // onDelete cascade: si se borra el inventario, se borran sus incidentes
            $table->foreignId('inventario_id')->constrained('inventarios')->onDelete('cascade');
            $table->string('tipo'); // e.g., 'Falla', 'Mantenimiento', 'Cambio Usuario'
            $table->text('descripcion')->nullable();
            $table->date('fecha_reporte')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidentes');
    }
};
