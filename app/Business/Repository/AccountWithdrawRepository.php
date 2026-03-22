<?php

declare(strict_types=1);

namespace App\Business\Repository;

use App\Model\Account;
use App\Model\AccountWithdraw;
use Hyperf\Collection\Collection;

class AccountWithdrawRepository
{
    public function storeWithDrawData(array $data): array
    {
        return AccountWithdraw::query()->create($data)->toArray();
    }

    public function getPendingWithDraws(string $dateTime): Collection
    {
        return AccountWithdraw::query()
            ->with(['pix'])
            ->where('scheduled', true)
            ->where('done', false)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', $dateTime)
            ->get();
    }

    public function getNextPendingScheduledFor(): ?string
    {
        $result = AccountWithdraw::query()
            ->where('scheduled', true)
            ->where('done', false)
            ->whereNotNull('scheduled_for')
            ->orderBy('scheduled_for')
            ->value('scheduled_for');

        if ($result === null) {
            return null;
        }

        return $result->toDateTimeString();
    }

    public function getAndLockWithdrawPix(string $withDrawId) : ?AccountWithdraw
    {
        return AccountWithdraw::query()
            ->with(['pix'])
            ->lockForUpdate()
            ->find($withDrawId);
    }

    public function getAndLockAccount(string $accountId) : ?Account
    {
        return Account::query()->lockForUpdate()->find($accountId);
    }
}
