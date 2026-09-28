<?php

namespace App\Support;

/**
 * Rupee amounts in words the way Pakistani invoices write them,
 * using lakh and crore: 95600 => "Ninety-Five Thousand Six Hundred Rupees Only".
 */
class AmountInWords
{
    private const ONES = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];

    private const TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    public static function rupees(float $amount): string
    {
        $rupees = (int) floor(abs($amount));
        $paisa = (int) round((abs($amount) - $rupees) * 100);

        $words = $rupees === 0 ? 'Zero' : self::number($rupees);
        $text = $words.' '.($rupees === 1 ? 'Rupee' : 'Rupees');
        if ($paisa > 0) {
            $text .= ' and '.self::number($paisa).' Paisa';
        }

        return $text.' Only';
    }

    private static function number(int $n): string
    {
        $parts = [];
        foreach ([[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand'], [100, 'Hundred']] as [$size, $name]) {
            if ($n >= $size) {
                $parts[] = self::number(intdiv($n, $size)).' '.$name;
                $n %= $size;
            }
        }
        if ($n > 0) {
            $parts[] = $n < 20 ? self::ONES[$n] : self::TENS[intdiv($n, 10)].($n % 10 ? '-'.self::ONES[$n % 10] : '');
        }

        return implode(' ', $parts);
    }
}
