<?php

declare(strict_types=1);

namespace App\Business\Service;

use App\Business\Repository\AccountWithdrawRepository;

class AccountWithdrawService
{
    public function __construct(private readonly AccountWithdrawRepository $repository)
    {
    }

    public function store(array $data): array
    {
        //return $this->repository->store($data);
    }
}
