<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'quantity', 'unit_price', 'unit_cost'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subtotal(): float
    {
        return (float) ($this->quantity * $this->unit_price);
    }

    /**
     * Sold lines whose product had no cost price at the time of sale. Their cost counts as zero in
     * profit, so pages show this to say the profit is higher than the real figure.
     *
     * @param  iterable<OrderItem>  $items
     * @return array{products: int, sales: float}
     */
    public static function missingCost(iterable $items): array
    {
        $missing = collect($items)->filter(fn (OrderItem $i) => $i->unit_cost === null);

        return [
            'products' => $missing->pluck('product_id')->unique()->count(),
            'sales' => (float) $missing->sum(fn (OrderItem $i) => $i->subtotal()),
        ];
    }
}
