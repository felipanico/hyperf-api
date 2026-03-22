<?php

declare(strict_types=1);

namespace App\Exception;

class InvalidWithdrawScheduleException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(422, 'Scheduled withdraw cannot be set in the past.');
    }
}
