<?php

namespace App\Models\Concerns;

use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Shared payment maths for bills (orders and purchases). Payments are
 * Transaction rows of PAYMENT_TYPE referencing the bill; the bill's own
 * BILL_TYPE transaction carries a status derived from them.
 *
 * Requires: transactions() morph relation, total(), isPayable().
 */
trait TracksPayments
{
    public function payments(): Collection
    {
        return $this->transactions->where('type', static::PAYMENT_TYPE)->sortBy('transaction_date')->values();
    }

    public function amountPaid(): float
    {
        return abs((float) $this->transactions->where('type', static::PAYMENT_TYPE)->sum('amount'));
    }

    public function balanceDue(): float
    {
        return $this->isPayable() ? max(0, round($this->total() - $this->amountPaid(), 2)) : 0.0;
    }

    public function paymentStatus(): string
    {
        $paid = $this->amountPaid();

        return match (true) {
            $this->total() > 0 && $paid >= $this->total() => 'paid',
            $paid > 0 => 'partial',
            default => 'unpaid',
        };
    }

    /**
     * Recalculate the bill transaction's status after payments change.
     */
    public function syncPaymentStatus(): void
    {
        $this->load('transactions', 'items');
        $status = $this->paymentStatus();

        $this->transactions()->where('type', static::BILL_TYPE)->update(['status' => $status]);
        $this->afterPaymentStatusSynced($status);
    }

    protected function afterPaymentStatusSynced(string $status): void
    {
        //
    }

    protected function newPayment(float $amount, string $date, ?string $note, string $partyColumn, int $partyId): Transaction
    {
        return Transaction::create([
            'type' => static::PAYMENT_TYPE,
            'reference_type' => static::class,
            'reference_id' => $this->id,
            $partyColumn => $partyId,
            'description' => $note,
            'amount' => static::PAYMENT_TYPE === 'payment_out' ? -$amount : $amount,
            'status' => 'paid',
            'transaction_date' => $date,
        ]);
    }
}
