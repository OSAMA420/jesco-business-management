<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');       // expenses only, see FinanceController::EXPENSE_CATEGORIES
            $table->string('payment_method')->nullable()->after('category');    // cash, bank, cheque
        });

        // Expenses recorded before categories existed.
        DB::table('transactions')->where('type', 'expense')->where('description', 'like', '%electric%')->update(['category' => 'utilities']);
        DB::table('transactions')->where('type', 'expense')->whereNull('category')->update(['category' => 'other']);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['category', 'payment_method']);
        });
    }
};
