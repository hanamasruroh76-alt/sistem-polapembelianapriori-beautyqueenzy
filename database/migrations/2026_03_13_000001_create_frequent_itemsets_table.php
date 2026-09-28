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
        Schema::create('frequent_itemsets', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->comment('Itemset level: 1 (L1), 2 (L2), 3 (L3), dst');
            $table->string('itemset')->comment('Representasi itemset, contoh: Roti, Susu');
            $table->unsignedInteger('count')->comment('Jumlah transaksi yang memuat itemset');
            $table->decimal('support', 8, 4)->comment('Nilai support dalam persen (0-100)');
            $table->timestamps();

            $table->index('level');
            $table->index('support');
            $table->index('itemset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frequent_itemsets');
        
    }
};
