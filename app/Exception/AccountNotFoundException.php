<?php

declare(strict_types=1);

namespace App\Exception;

class AccountNotFoundException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(404, 'Account not found.');
    }
}
