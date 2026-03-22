<?php

declare(strict_types=1);

namespace App\Business\Service\Withdraw;

use App\Contract\WithdrawMethodServiceInterface;
use App\Enum\WithdrawMethod;
use App\Exception\InsufficientBalanceException;
use App\Exception\UnsupportedWithdrawMethodException;
use App\Model\Account;
use DateTimeImmutable;
use ValueError;

class WithdrawExecutionService
{
    public function __construct(private readonly WithdrawMethodServiceInterface $pixService)
    {
    }

    public function resolveMethod(string $method): WithdrawMethod
    {
        try {
            return WithdrawMethod::from($method);
        } catch (ValueError) {
            throw new UnsupportedWithdrawMethodException();
        }
    }

    public function resolveMethodService(WithdrawMethod $method): WithdrawMethodServiceInterface
    {
        return match ($method) {
            WithdrawMethod::PIX => $this->pixService,
        };
    }

    public function assertSufficientBalance(Account $account, string $amount): void
    {
        if ($this->toCents((string) $account->balance) < $this->toCents($amount)) {
            throw new InsufficientBalanceException();
        }
    }

    public function deductAccountBalance(Account $account, string $amount): void
    {
        $newBalanceInCents = $this->toCents((string) $account->balance) - $this->toCents($amount);

        if ($newBalanceInCents < 0) {
            throw new InsufficientBalanceException();
        }

        $account->update([
            'balance' => number_format($newBalanceInCents / 100, 2, '.', ''),
        ]);
    }

    public function sendNotification(
        WithdrawMethodServiceInterface $methodService,
        array $data,
        string $amount,
        DateTimeImmutable $processedAt,
    ): void {
        $methodService->sendNotification($data, $amount, $processedAt);
    }

    public function toCents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
