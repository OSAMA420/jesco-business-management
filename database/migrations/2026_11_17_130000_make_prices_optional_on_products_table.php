<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cost and selling price are optional now. NULL means "not set yet", which is different from a
     * real price of 0: profit and stock value treat a missing cost as unknown and say so. A sale of a
     * product with no cost keeps a NULL unit_cost for the same reason.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->default(null)->change();
            $table->decimal('selling_price', 12, 2)->nullable()->default(null)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        foreach (['cost_price', 'selling_price'] as $column) {
            DB::table('products')->whereNull($column)->update([$column => 0]);
        }
        DB::table('order_items')->whereNull('unit_cost')->update(['unit_cost' => 0]);

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable(false)->default(0)->change();
            $table->decimal('selling_price', 12, 2)->nullable(false)->default(0)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->nullable(false)->default(0)->change();
        });
    }
};
