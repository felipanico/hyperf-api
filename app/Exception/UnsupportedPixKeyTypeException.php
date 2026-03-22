<?php

declare(strict_types=1);

namespace App\Exception;

class UnsupportedPixKeyTypeException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(422, 'Only PIX email keys are supported.');
    }
}
