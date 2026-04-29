<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // ID de la App Madre
            $table->string('username')->nullable();
            $table->string('name');
            $table->string('avatar')->nullable(); // Ruta de la imagen / foto
            $table->string('puesto')->nullable();
            $table->json('roles_list')->nullable();
            $table->json('permisos_list')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
