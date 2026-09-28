<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records a payment from the customer/supplier page, either against one
 * chosen bill or spread over open bills oldest first.
 */
class PartyPayment
{
    /**
     * @param  Collection<int, Model>  $openBills  bills with a balance due, oldest first
     * @return float the amount recorded
     */
    public static function apply(Request $request, Collection $openBills): float
    {
        $totalDue = round($openBills->sum(fn ($bill) => $bill->balanceDue()), 2);

        $data = $request->validate([
            'apply_to' => ['required', 'string'],
            'payment_amount' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'payment_note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['apply_to'] === 'auto') {
            $targets = $openBills;
            $limit = $totalDue;
        } else {
            $bill = $openBills->firstWhere('id', (int) $data['apply_to']);
            if (! $bill) {
                throw ValidationException::withMessages(['apply_to' => 'That bill has nothing due, choose another one.']);
            }
            $targets = collect([$bill]);
            $limit = $bill->balanceDue();
        }

        $amount = round((float) $data['payment_amount'], 2);
        if ($limit <= 0) {
            throw ValidationException::withMessages(['payment_amount' => 'Nothing is due right now.']);
        }
        if ($amount > $limit) {
            throw ValidationException::withMessages(['payment_amount' => 'Amount cannot be more than the Rs. '.number_format($limit).' due.']);
        }

        DB::transaction(function () use ($targets, $amount, $data) {
            $remaining = $amount;

            foreach ($targets as $bill) {
                if ($remaining <= 0) {
                    break;
                }

                $portion = min($bill->balanceDue(), $remaining);
                $bill->recordPayment($portion, $data['payment_date'], $data['payment_note'] ?? null);
                $remaining = round($remaining - $portion, 2);
            }
        });

        return $amount;
    }
}
