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
        Schema::create('manutencao_programada', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // sempre ID=1 (singleton)
            $table->boolean('ativo')->default(false);
            $table->string('titulo', 200)->default('Manutenção Programada');
            $table->text('mensagem')->nullable();
            $table->dateTime('inicio_programado')->nullable();
            $table->dateTime('fim_programado')->nullable();
            $table->string('criado_por', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manutencao_programada');
    }
};
