<?php

declare(strict_types=1);

namespace App\Contract;

interface WithdrawMethodValidationInterface
{
    public static function rules(array $input = []): array;

    public static function messages(array $input = []): array;
}
