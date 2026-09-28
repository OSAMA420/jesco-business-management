<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An "ordered" purchase has no transaction yet, so the payment status chosen
     * at creation has to live on the purchase until the stock is received.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status'); // paid, partial, unpaid
        });

        DB::table('transactions')
            ->where('type', 'purchase')
            ->where('reference_type', 'App\\Models\\Purchase')
            ->get(['reference_id', 'status'])
            ->each(fn ($t) => DB::table('purchases')->where('id', $t->reference_id)->update(['payment_status' => $t->status]));
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
