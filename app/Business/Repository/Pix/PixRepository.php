<?php

declare(strict_types=1);

namespace App\Business\Repository\Pix;

use App\Model\AccountWithdrawPix;

class PixRepository
{
    public function storePixData(array $data): array
    {
        return AccountWithdrawPix::query()->create($data)->toArray();
    }
}
