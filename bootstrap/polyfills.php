<?php

// Wasmer Edge's PHP build ships mbstring without its regex functions, but
// Laravel's Str helpers call mb_split('\s+', ...). Only defined when missing.
if (! function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array|false
    {
        return preg_split('/'.str_replace('/', '\/', $pattern).'/u', $string, $limit);
    }
}
