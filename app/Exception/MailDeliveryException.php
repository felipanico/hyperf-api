<?php

declare(strict_types=1);

namespace App\Exception;

class MailDeliveryException extends BusinessException
{
    public function __construct(string $message = 'Unable to deliver e-mail notification.')
    {
        parent::__construct(500, $message);
    }
}
