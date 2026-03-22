<?php

declare(strict_types=1);

namespace App\Controller;

use App\Business\Service\AccountWithdrawService;
use App\Request\StoreAccountWithdrawRequest;

class AccountWithdrawController extends AbstractController
{
    public function __construct(private readonly AccountWithdrawService $service)
    {
    }

    public function store(string $accountId, StoreAccountWithdrawRequest $request): array
    {
        return $this->service->store([
            'account_id' => $accountId,
            ...$request->validated(),
        ]);
    }
}
