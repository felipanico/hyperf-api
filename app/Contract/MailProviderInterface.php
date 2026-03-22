<?php

declare(strict_types=1);

namespace App\Contract;

use App\DTO\MailMessage;

interface MailProviderInterface
{
    public function send(MailMessage $message): void;
}
