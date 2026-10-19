<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\Rule;

class Transaction extends Model
{
    /** How money was paid: used by payments and expenses alike. */
    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank' => 'Bank Transfer',
        'wallet' => 'JazzCash / Easypaisa',
        'cheque' => 'Cheque',
    ];

    protected $fillable = [
        'type', 'reference_type', 'reference_id',
        'customer_id', 'supplier_id', 'description', 'category', 'payment_method', 'reference_no',
        'amount', 'status', 'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /** Where money sits. A cheque ends up in the bank, so it counts as Bank. */
    public const ACCOUNTS = [
        'cash' => 'Cash in Hand',
        'bank' => 'Bank',
        'wallet' => 'JazzCash / Easypaisa',
    ];

    private const METHOD_ACCOUNT = ['cash' => 'cash', 'bank' => 'bank', 'cheque' => 'bank', 'wallet' => 'wallet'];

    public function methodLabel(): ?string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? null;
    }

    /** The account this money went into or came out of, or null when the method wasn't recorded. */
    public function account(): ?string
    {
        return self::METHOD_ACCOUNT[$this->payment_method] ?? null;
    }

    /** Payment methods that land in the given account, for queries. */
    public static function methodsFor(string $account): array
    {
        return array_keys(array_filter(self::METHOD_ACCOUNT, fn ($a) => $a === $account));
    }

    /**
     * How this entry changes the money on hand: payments in add, payments out and
     * expenses take away; sales and purchases are bills, not money.
     */
    public function cashEffect(): float
    {
        return match ($this->type) {
            'payment_in', 'opening' => abs((float) $this->amount),
            'payment_out', 'expense' => -abs((float) $this->amount),
            default => 0.0,
        };
    }

    /**
     * Validation for the "how was it paid" fields on every payment form.
     */
    public static function paymentMethodRules(string $methodField = 'payment_method', string $referenceField = 'payment_reference'): array
    {
        return [
            $methodField => ['required', Rule::in(array_keys(self::PAYMENT_METHODS))],
            $referenceField => ['nullable', 'string', 'max:100'],
        ];
    }
}
