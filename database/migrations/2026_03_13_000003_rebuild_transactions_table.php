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
        // Hapus tabel lama (child dulu sebelum parent)
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');

        // Buat tabel transaksi baru dengan struktur sederhana
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->comment('Kode / ID transaksi');
            $table->string('nama_barang')->comment('Nama barang yang dibeli');
            $table->timestamps();

            $table->index('kode_transaksi');
            $table->index('nama_barang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->timestamp('transaction_date')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->index('transaction_date');
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->string('item_name');
            $table->string('item_category')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
            $table->index('item_name');
            $table->unique(['transaction_id', 'item_name']);
        });
    }
};
