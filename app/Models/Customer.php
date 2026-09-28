<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'company', 'phone', 'email', 'address'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Amount this customer still owes: everything billed minus every payment received.
     */
    public function balance(): float
    {
        $billed = $this->transactions()->where('type', 'sale')->sum('amount');
        $received = $this->transactions()->where('type', 'payment_in')->sum('amount');

        return (float) round($billed - $received, 2);
    }
}
