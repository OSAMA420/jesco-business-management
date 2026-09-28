<?php

/*
| Business details printed on invoices. Taken from jesco.pk; change them here
| (or through .env) if they move, get an NTN, or add an email.
*/

return [
    'legal_name' => env('JESCO_LEGAL_NAME', 'PAK OIL LUBRICANTS'),
    'address' => env('JESCO_ADDRESS', 'Shop no. 3, Gali no. 5, Yousuf Goth, Near Quetta Terminal, Baldia Town, Karachi'),
    'phone' => env('JESCO_PHONE', '0320-8853243'),
    'email' => env('JESCO_EMAIL'),
    'website' => env('JESCO_WEBSITE', 'www.jesco.pk'),
    'ntn' => env('JESCO_NTN'),
    'tagline' => 'Premium vehicle engine oils for smooth performance and longer engine life',
];
