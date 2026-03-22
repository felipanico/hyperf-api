<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'host' => env('MAIL_HOST', 'mailhog'),
    'port' => (int) env('MAIL_PORT', 1025),
    'timeout' => (float) env('MAIL_TIMEOUT', 5),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@api-pix.local'),
    ],
];
