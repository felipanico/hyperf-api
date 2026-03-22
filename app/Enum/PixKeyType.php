<?php

declare(strict_types=1);

namespace App\Enum;

enum PixKeyType: string
{
    case EMAIL = 'email';

    public function keyRules(): array
    {
        return match ($this) {
            self::EMAIL => ['required', 'string', 'email'],
        };
    }

    public function keyMessages(): array
    {
        return array_merge(
            self::defaultKeyMessages(),
            match ($this) {
                self::EMAIL => [
                    'pix.key.email' => 'The pix.key field must be a valid email address.',
                ],
            },
        );
    }

    public static function values(): array
    {
        $values = [];

        foreach (self::cases() as $type) {
            $values[] = $type->value;
        }

        return $values;
    }

    public static function keyRulesFor(?string $type): array
    {
        if ($type === null || $type === '') {
            return ['required', 'string'];
        }

        foreach (self::cases() as $pixKeyType) {
            if ($pixKeyType->value === $type) {
                return $pixKeyType->keyRules();
            }
        }

        return ['required', 'string'];
    }

    public static function keyMessagesFor(?string $type): array
    {
        if ($type === null || $type === '') {
            return self::defaultKeyMessages();
        }

        foreach (self::cases() as $pixKeyType) {
            if ($pixKeyType->value === $type) {
                return $pixKeyType->keyMessages();
            }
        }

        return self::defaultKeyMessages();
    }

    private static function defaultKeyMessages(): array
    {
        return [
            'pix.key.required' => 'The pix.key field is required.',
        ];
    }
}
