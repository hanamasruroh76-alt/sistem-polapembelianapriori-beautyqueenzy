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
        Schema::dropIfExists('apriori_item_sets');
        Schema::dropIfExists('association_rules');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('apriori_item_sets', function (Blueprint $table) {
            $table->id();
            $table->string('items');
            $table->decimal('support', 5, 4)->comment('Support value 0-1');
            $table->integer('count')->comment('Frequency count');
            $table->integer('itemset_size')->comment('Number of items in set');
            $table->integer('trend')->nullable()->comment('Trend percentage');
            $table->decimal('min_support', 5, 4)->nullable();
            $table->timestamps();
            $table->index('support');
            $table->index('itemset_size');
        });

        Schema::create('association_rules', function (Blueprint $table) {
            $table->id();
            $table->string('antecedent')->comment('A in rule A=>B');
            $table->string('consequent')->comment('B in rule A=>B');
            $table->decimal('support', 5, 4)->comment('Support of A and B');
            $table->decimal('confidence', 5, 4)->comment('Confidence A=>B');
            $table->decimal('lift', 8, 4)->comment('Lift of rule');
            $table->decimal('leverage', 8, 6)->nullable();
            $table->decimal('conviction', 8, 4)->nullable();
            $table->decimal('min_support', 5, 4)->nullable();
            $table->decimal('min_confidence', 5, 4)->nullable();
            $table->timestamps();
            $table->index('confidence');
            $table->index('lift');
        });
    }
};
