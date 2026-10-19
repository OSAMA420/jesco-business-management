<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments now record how they were paid (payment_method, which expenses
     * already use) and an optional reference: cheque number, transaction ID.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('reference_no')->nullable()->after('payment_method');
        });

        // Sample payments whose note already says how they were paid.
        DB::table('transactions')->whereIn('type', ['payment_in', 'payment_out'])->whereNull('payment_method')
            ->where('description', 'Cash')->update(['payment_method' => 'cash']);
        DB::table('transactions')->whereIn('type', ['payment_in', 'payment_out'])->whereNull('payment_method')
            ->where('description', 'Bank transfer')->update(['payment_method' => 'bank']);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('reference_no');
        });
    }
};
