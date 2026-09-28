<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index()
    {
        $summary = [
            'total_sales' => 1245000,
            'total_purchases' => 640000,
            'total_expenses' => 128500,
            'outstanding' => 57500,
        ];

        $transactions = collect([
            ['date' => '2026-09-21', 'type' => 'sale', 'ref' => 'ORD-1042', 'party' => 'Bilal Khan', 'amount' => 24500, 'status' => 'paid'],
            ['date' => '2026-09-20', 'type' => 'purchase', 'ref' => 'PO-5521', 'party' => 'Al-Rahim Base Oil Traders', 'amount' => -88000, 'status' => 'paid'],
            ['date' => '2026-09-20', 'type' => 'expense', 'ref' => 'EXP-221', 'party' => 'Electricity Bill', 'amount' => -14200, 'status' => 'paid'],
            ['date' => '2026-09-19', 'type' => 'sale', 'ref' => 'ORD-1040', 'party' => 'Hassan Raza', 'amount' => 98200, 'status' => 'partial'],
            ['date' => '2026-09-18', 'type' => 'sale', 'ref' => 'ORD-1038', 'party' => 'Usman Ahmed', 'amount' => 33000, 'status' => 'unpaid'],
        ]);

        return view('finance.index', compact('summary', 'transactions'));
    }
}
