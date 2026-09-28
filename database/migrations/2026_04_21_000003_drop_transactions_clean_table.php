<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('transactions_clean');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('transactions_clean', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi');
            $table->string('nama_barang');
            $table->timestamps();

            $table->index('kode_transaksi');
            $table->index('nama_barang');
        });
    }
};