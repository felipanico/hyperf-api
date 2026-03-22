<?php

declare(strict_types=1);

namespace App\Business\Repository;

use App\Model\AccountWithdraw;

class AccountWithdrawRepository
{
    public function storeWithDrawData(array $data): array
    {
        return AccountWithdraw::query()->create($data)->toArray();
    }
}
