<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $reports = [
            ['title' => 'Sales Report', 'description' => 'Daily, monthly and yearly sales breakdown by product and customer.', 'icon' => 'sales'],
            ['title' => 'Inventory Report', 'description' => 'Current stock levels, stock movement and low-stock summary.', 'icon' => 'inventory'],
            ['title' => 'Purchase Report', 'description' => 'Purchases made from suppliers over a selected date range.', 'icon' => 'purchase'],
            ['title' => 'Financial Report', 'description' => 'Income, expenses, profit & loss and outstanding balances.', 'icon' => 'finance'],
            ['title' => 'Customer Ledger', 'description' => 'Complete transaction history and balance per customer.', 'icon' => 'ledger'],
            ['title' => 'Order Summary', 'description' => 'Orders grouped by status, customer and time period.', 'icon' => 'orders'],
        ];

        return view('reports.index', compact('reports'));
    }
}
