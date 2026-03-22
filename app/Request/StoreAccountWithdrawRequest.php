<?php

declare(strict_types=1);

namespace App\Request;

use App\Enum\WithdrawMethod;
use Hyperf\HttpServer\Request;

class StoreAccountWithdrawRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            $this->baseRules(),
            WithdrawMethod::rulesFor((string) $this->input('method'), $this->all()),
        );
    }

    public function messages(): array
    {
        return array_merge(
            $this->baseMessages(),
            WithdrawMethod::messagesFor((string) $this->input('method'), $this->all()),
        );
    }

    private function baseRules(): array
    {
        return [
            'method' => ['required', 'string', 'in:' . implode(',', WithdrawMethod::values())],
            'amount' => ['required', 'numeric', 'gt:0'],
            'schedule' => ['nullable', 'date_format:Y-m-d H:i'],
        ];
    }

    private function baseMessages(): array
    {
        return [
            'method.required' => 'The method field is required.',
            'method.in' => 'The method must be a supported withdraw method.',
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount field must be numeric.',
            'amount.gt' => 'The amount field must be greater than zero.',
            'schedule.date_format' => 'The schedule field must match Y-m-d H:i.',
        ];
    }
}
