<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\HttpServer\Request;

class StoreAccountWithdrawRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', 'in:PIX'],
            'pix' => ['required', 'array'],
            'pix.type' => ['required', 'string'],
            'pix.key' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'schedule' => ['nullable', 'date_format:Y-m-d H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'method.required' => 'The method field is required.',
            'method.in' => 'The method must be PIX.',
            'pix.required' => 'The pix field is required.',
            'pix.array' => 'The pix field must be an object.',
            'pix.type.required' => 'The pix.type field is required.',
            'pix.key.required' => 'The pix.key field is required.',
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount field must be numeric.',
            'amount.gt' => 'The amount field must be greater than zero.',
            'schedule.date_format' => 'The schedule field must match Y-m-d H:i.',
        ];
    }
}