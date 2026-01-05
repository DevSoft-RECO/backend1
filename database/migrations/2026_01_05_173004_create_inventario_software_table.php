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
        Schema::create('inventario_software', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->index();
            $table->string('enlace')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('tipo')->index();
            $table->string('usuario')->nullable();
            $table->string('clave')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_software');
    }
};
