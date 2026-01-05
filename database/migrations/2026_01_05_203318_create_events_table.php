<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            // Relaciones
            // IMPORTANTE: No hay clave foránea a users porque la tabla no existe en esta app
            $table->unsignedBigInteger('user_id');
            $table->foreignId('event_category_id')->nullable()->constrained('event_categories')->nullOnDelete();

            // Datos Básicos
            $table->string('title');
            $table->text('description')->nullable();

            // Tiempos
            $table->dateTime('start');
            $table->dateTime('end');
            $table->boolean('is_all_day')->default(false);

            // Contexto
            $table->string('location')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
