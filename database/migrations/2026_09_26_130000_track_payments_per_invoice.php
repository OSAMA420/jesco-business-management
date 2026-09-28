<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moves from a hand-picked payment status to payments recorded against each
 * order/purchase:
 *  - "paid" bills get a payment row for their full amount
 *  - "partial" bills never recorded how much was paid, so they start at zero
 *  - older party-level payments (no bill attached) are applied oldest bill first
 * Every bill's status is then recalculated from its payments.
 */
return new class extends Migration
{
    private const ORDER = 'App\\Models\\Order';

    private const PURCHASE = 'App\\Models\\Purchase';

    public function up(): void
    {
        $sides = [
            ['bill' => 'sale', 'payment' => 'payment_in', 'model' => self::ORDER, 'party' => 'customer_id', 'sign' => 1],
            ['bill' => 'purchase', 'payment' => 'payment_out', 'model' => self::PURCHASE, 'party' => 'supplier_id', 'sign' => -1],
        ];

        foreach ($sides as $side) {
            $bills = DB::table('transactions')->where('type', $side['bill'])->where('reference_type', $side['model'])->get();

            foreach ($bills->where('status', 'paid') as $bill) {
                DB::table('transactions')->insert([
                    'type' => $side['payment'],
                    'reference_type' => $side['model'],
                    'reference_id' => $bill->reference_id,
                    $side['party'] => $bill->{$side['party']},
                    'description' => 'Paid in full (recorded before per-bill payments)',
                    'amount' => $bill->amount,
                    'status' => 'paid',
                    'transaction_date' => $bill->transaction_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->applyLoosePayments($side);

            foreach ($bills as $bill) {
                $this->syncStatus($side, $bill);
            }
        }
    }

    public function down(): void
    {
        // Not reversible: the old hand-picked statuses cannot be reconstructed.
    }

    private function applyLoosePayments(array $side): void
    {
        $loose = DB::table('transactions')
            ->where('type', $side['payment'])
            ->whereNull('reference_id')
            ->whereNotNull($side['party'])
            ->orderBy('transaction_date')->orderBy('id')
            ->get();

        foreach ($loose as $payment) {
            $remaining = abs((float) $payment->amount);
            $first = true;

            $openBills = DB::table('transactions')
                ->where('type', $side['bill'])
                ->where($side['party'], $payment->{$side['party']})
                ->orderBy('transaction_date')->orderBy('id')
                ->get();

            foreach ($openBills as $bill) {
                $due = abs((float) $bill->amount) - $this->paidTowards($side, $bill->reference_id);
                if ($due <= 0 || $remaining <= 0) {
                    continue;
                }

                $portion = min($due, $remaining);
                $remaining -= $portion;

                if ($first) {
                    DB::table('transactions')->where('id', $payment->id)->update([
                        'reference_type' => $side['model'],
                        'reference_id' => $bill->reference_id,
                        'amount' => $side['sign'] * $portion,
                    ]);
                    $first = false;
                } else {
                    DB::table('transactions')->insert([
                        'type' => $side['payment'],
                        'reference_type' => $side['model'],
                        'reference_id' => $bill->reference_id,
                        $side['party'] => $payment->{$side['party']},
                        'description' => $payment->description,
                        'amount' => $side['sign'] * $portion,
                        'status' => 'paid',
                        'transaction_date' => $payment->transaction_date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Anything left over stays as unattached credit for the party.
            if (! $first && $remaining > 0) {
                DB::table('transactions')->insert([
                    'type' => $side['payment'],
                    $side['party'] => $payment->{$side['party']},
                    'description' => $payment->description,
                    'amount' => $side['sign'] * $remaining,
                    'status' => 'paid',
                    'transaction_date' => $payment->transaction_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function paidTowards(array $side, int $billId): float
    {
        return abs((float) DB::table('transactions')
            ->where('type', $side['payment'])
            ->where('reference_type', $side['model'])
            ->where('reference_id', $billId)
            ->sum('amount'));
    }

    private function syncStatus(array $side, object $bill): void
    {
        $total = abs((float) $bill->amount);
        $paid = $this->paidTowards($side, $bill->reference_id);
        $status = $total > 0 && $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        DB::table('transactions')->where('id', $bill->id)->update(['status' => $status]);

        if ($side['model'] === self::PURCHASE) {
            DB::table('purchases')->where('id', $bill->reference_id)->update(['payment_status' => $status]);
        }
    }
};
