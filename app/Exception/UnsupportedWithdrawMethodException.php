<?php

declare(strict_types=1);

namespace App\Exception;

class UnsupportedWithdrawMethodException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(422, 'Unsupported withdraw method.');
    }
}
