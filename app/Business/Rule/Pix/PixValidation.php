<?php

declare(strict_types=1);

namespace App\Business\Rule\Pix;

use App\Contract\WithdrawMethodValidationInterface;
use App\Enum\PixKeyType;

final class PixValidation implements WithdrawMethodValidationInterface
{
    public static function rules(array $input = []): array
    {
        return [
            'pix' => ['required', 'array'],
            'pix.type' => ['required', 'string', 'in:' . implode(',', PixKeyType::values())],
            'pix.key' => PixKeyType::keyRulesFor($input['pix']['type'] ?? null),
        ];
    }

    public static function messages(array $input = []): array
    {
        return array_merge(
            [
                'pix.required' => 'The pix field is required.',
                'pix.array' => 'The pix field must be an object.',
                'pix.type.required' => 'The pix.type field is required.',
                'pix.type.in' => 'The pix.type field must be a supported PIX type.',
            ],
            PixKeyType::keyMessagesFor($input['pix']['type'] ?? null),
        );
    }
}
