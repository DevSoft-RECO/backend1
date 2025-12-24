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
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            // Relaciones
            $table->foreignId('agencia_id')->constrained('agencias')->onDelete('cascade');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');

            // Identificadores
            $table->string('codigo_activo')->nullable()->comment('Codigo unico de activo fijo');
            $table->string('numero_serie')->nullable();

            // Ubicación y Responsable
            $table->string('area')->nullable();
            $table->string('nombre_responsable')->nullable();
            $table->string('puesto_responsable')->nullable();

            // Datos de Acceso
            $table->string('usuario_equipo')->nullable();
            $table->string('password_equipo')->nullable();

            // Detalles Técnicos
            $table->string('tipo_dispositivo')->nullable(); // Puede ser redundante con Categoria, pero valida
            $table->boolean('activo')->default(true); // Estado logico
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('nombre_equipo')->nullable(); // Hostname
            $table->string('ip_address')->nullable();
            $table->string('procesador')->nullable();
            $table->string('memoria_ram')->nullable();
            $table->string('almacenamiento')->nullable();

            // Software
            $table->string('sistema_operativo')->nullable();
            $table->string('licencia_so')->nullable();
            $table->string('version_office')->nullable();
            $table->string('licencia_office')->nullable();
            $table->string('antivirus')->nullable();

            // Seguridad y Otros
            $table->string('bloqueo_usb')->nullable(); // Puede ser 'Si', 'No' o descripcion
            $table->boolean('es_remoto')->default(false);
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
