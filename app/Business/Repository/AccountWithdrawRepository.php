<?php

declare(strict_types=1);

namespace App\Business\Repository;

use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;

class AccountWithdrawRepository
{
    public function storeWithDrawData(array $data): array
    {
        return AccountWithdraw::query()->create($data)->toArray();
    }

    public function storePixData(array $data): array
    {
        return AccountWithdrawPix::query()->create($data)->toArray();
    }
}
