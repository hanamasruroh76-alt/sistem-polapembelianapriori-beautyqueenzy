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
        if (Schema::hasTable('frequent_itemsets') && Schema::hasColumn('frequent_itemsets', 'items')) {
            Schema::table('frequent_itemsets', function (Blueprint $table) {
                $table->dropColumn('items');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('frequent_itemsets') && ! Schema::hasColumn('frequent_itemsets', 'items')) {
            Schema::table('frequent_itemsets', function (Blueprint $table) {
                $table->json('items')->nullable()->after('level')->comment('Daftar item dalam bentuk array JSON');
            });
        }
    }
};
