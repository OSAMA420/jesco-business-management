<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The product's cost at the moment it was sold, so profit on old orders
     * doesn't shift when purchase prices change later.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->default(0)->after('unit_price');
        });

        // Orders placed before this existed: best available figure is today's cost price.
        DB::table('order_items')->orderBy('id')->each(function ($item) {
            $cost = DB::table('products')->where('id', $item->product_id)->value('cost_price') ?? 0;
            DB::table('order_items')->where('id', $item->id)->update(['unit_cost' => $cost]);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
