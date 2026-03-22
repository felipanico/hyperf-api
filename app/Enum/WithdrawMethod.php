<?php

declare(strict_types=1);

namespace App\Enum;

use App\Business\Rule\Pix\PixValidation;

enum WithdrawMethod: string
{
    case PIX = 'PIX';

    public function rules(array $input = []): array
    {
        return match ($this) {
            self::PIX => PixValidation::rules($input),
        };
    }

    public function messages(array $input = []): array
    {
        return match ($this) {
            self::PIX => PixValidation::messages($input),
        };
    }

    public static function values(): array
    {
        $values = [];

        foreach (self::cases() as $method) {
            $values[] = $method->value;
        }

        return $values;
    }

    public static function rulesFor(?string $method, array $input = []): array
    {
        if ($method === null || $method === '') {
            return [];
        }

        foreach (self::cases() as $withdrawMethod) {
            if ($withdrawMethod->value === $method) {
                return $withdrawMethod->rules($input);
            }
        }

        return [];
    }

    public static function messagesFor(?string $method, array $input = []): array
    {
        if ($method === null || $method === '') {
            return [];
        }

        foreach (self::cases() as $withdrawMethod) {
            if ($withdrawMethod->value === $method) {
                return $withdrawMethod->messages($input);
            }
        }

        return [];
    }
}
