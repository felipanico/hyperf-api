<?php

declare(strict_types=1);

namespace App\Business\Repository;

use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;

class AccountWithdrawRepository
{
    public function storeWithDrawData(array $data): array
    {
        $withdraw = AccountWithdraw::query()->create([
            'id' => $data['id'] ?? (string) uniqid('', true),
            'account_id' => $data['account_id'],
            'method' => $data['method'],
            'amount' => $data['amount'],
            'scheduled' => ! empty($data['schedule']),
            'scheduled_for' => $data['schedule'] ?? null,
            'done' => false,
            'error' => false,
            'error_reason' => null,
        ]);

        return $withdraw->toArray();
    }

    public function storePixData(array $data): array
    {
        $pix = AccountWithdrawPix::query()->create([
            'account_withdraw_id' => $data['account_withdraw_id'],
            'type' => $data['pix']['type'],
            'key' => $data['pix']['key'],
        ]);

        return $pix->toArray();
    }
}
