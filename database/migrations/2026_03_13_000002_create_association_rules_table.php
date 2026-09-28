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
        Schema::create('association_rules', function (Blueprint $table) {
            $table->id();
            $table->string('antecedent')->comment('Bagian kiri rule (A) dari A => B');
            $table->string('consequent')->comment('Bagian kanan rule (B) dari A => B');
            $table->decimal('support', 8, 4)->comment('Nilai support dalam persen (0-100)');
            $table->decimal('confidence', 8, 4)->comment('Nilai confidence dalam persen (0-100)');
            $table->decimal('lift', 12, 6)->comment('Nilai lift rule');
            $table->timestamps();

            $table->index('confidence');
            $table->index('lift');
            $table->index(['antecedent', 'consequent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('association_rules');
    }
};
