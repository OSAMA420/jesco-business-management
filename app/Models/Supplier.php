<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'phone', 'address'];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Amount still owed to this supplier: everything billed minus every payment made.
     */
    public function balance(): float
    {
        $billed = abs((float) $this->transactions()->where('type', 'purchase')->sum('amount'));
        $paid = abs((float) $this->transactions()->where('type', 'payment_out')->sum('amount'));

        return (float) round($billed - $paid, 2);
    }
}
