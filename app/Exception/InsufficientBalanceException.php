<?php

declare(strict_types=1);

namespace App\Exception;

class InsufficientBalanceException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(422, 'Insufficient balance for withdraw.');
    }
}
