<?php

declare(strict_types=1);

namespace App\Controller;

use App\Business\Service\AccountWithdrawService;
use App\Request\StoreAccountWithdrawRequest;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

class AccountWithdrawController extends AbstractController
{
    public function __construct(private readonly AccountWithdrawService $service)
    {
    }

    /** @disregard P1009 Undefined type (falso positivo do inteliphense) */
    public function store(string $accountId, ValidatorFactoryInterface $validatorFactory): array
    {
        $form = new StoreAccountWithdrawRequest();

        $validator = $validatorFactory->make(
            $form->all(),
            $form->rules(),
            $form->messages()
        );

        $validated = $validator->validated();

        return $this->service->store([
            'account_id' => $accountId,
            ...$validated,
        ]);
    }
}
