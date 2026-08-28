<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §04 — distingue posto físico (box, elevador, cabine) de posto-pessoa
     * (mecânico), e permite ligar o posto-pessoa ao User técnico
     * correspondente, evitando cadastrar o mesmo mecânico duas vezes com
     * nomes que podem divergir.
     */
    public function up(): void
    {
        Schema::table('recursos', function (Blueprint $table) {
            $table->string('tipo', 10)->default('espaco')->after('nome'); // espaco | pessoa
            $table->foreignId('user_id')->nullable()->after('tipo')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recursos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('tipo');
        });
    }
};
