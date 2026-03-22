<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class MailMessage
{
    public function __construct(
        public string $from,
        public string $to,
        public string $subject,
        public string $text,
    ) {
    }
}
