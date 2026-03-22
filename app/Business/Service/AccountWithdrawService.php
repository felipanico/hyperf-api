<?php

declare(strict_types=1);

namespace App\Business\Service;

use App\Business\Repository\AccountWithdrawRepository;
use App\Enum\PixKeyType;
use App\Enum\WithdrawMethod;
use App\Exception\AccountNotFoundException;
use App\Exception\InsufficientBalanceException;
use App\Exception\InvalidWithdrawScheduleException;
use App\Exception\UnsupportedPixKeyTypeException;
use App\Exception\UnsupportedWithdrawMethodException;
use App\Model\Account;
use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Throwable;
use ValueError;

class AccountWithdrawService
{
    public function __construct(
        private readonly AccountWithdrawRepository $repository,
        private readonly MailService $mailService,
    ) {
    }

    public function store(array $data): array
    {
        $amount = $this->normalizeAmount($data['amount']);
        $schedule = $this->parseScheduleSafely($data['schedule'] ?? null);
        $processedAt = new DateTimeImmutable();

        try {
            $result = $this->processWithdraw($data, $amount, $schedule);
        } catch (Throwable $throwable) {
            $this->storeFailedWithdrawAttempt($data, $amount, $schedule, $throwable);
            throw $throwable;
        }

        if ($schedule === null) {
            $this->sendWithdrawNotification($data, $amount, $processedAt);
        }

        return $result;
    }

    private function processWithdraw(array $data, string $amount, ?DateTimeImmutable $schedule): array
    {
        $account = $this->findAccount($data['account_id']);
        $method = $this->resolveMethod($data['method']);

        $this->assertScheduleIsValid($schedule);
        $this->assertSufficientBalance($account, $amount);
        $this->validateMethodPayload($method, $data);

        return Db::transaction(function () use ($account, $data, $method, $schedule, $amount): array {
            $withdrawId = $this->generateUuid();
            $isScheduled = $schedule !== null;

            if (! $isScheduled) {
                $this->deductAccountBalance($account, $amount);
            }

            $withdraw = $this->repository->storeWithDrawData($this->buildWithdrawData(
                withdrawId: $withdrawId,
                accountId: (string) $account->id,
                method: $method->value,
                amount: $amount,
                schedule: $schedule,
                done: ! $isScheduled,
                error: false,
                errorReason: null,
            ));

            $details = $this->storeMethodData($method, $withdrawId, $data);

            return [
                'withdraw' => $withdraw,
                'details' => $details,
                'account' => $account->fresh()?->toArray(),
            ];
        });
    }

    private function storeFailedWithdrawAttempt(
        array $data,
        string $amount,
        ?DateTimeImmutable $schedule,
        Throwable $throwable,
    ): void {
        try {
            $this->repository->storeWithDrawData($this->buildWithdrawData(
                withdrawId: $this->generateUuid(),
                accountId: (string) ($data['account_id'] ?? ''),
                method: $this->resolveFailedMethod($data['method'] ?? null),
                amount: $amount,
                schedule: $schedule,
                done: false,
                error: true,
                errorReason: $throwable->getMessage(),
            ));
        } catch (Throwable) {
        }
    }

    private function findAccount(string $accountId): Account
    {
        $account = Account::query()->find($accountId);

        if (! $account instanceof Account) {
            throw new AccountNotFoundException();
        }

        return $account;
    }

    private function resolveMethod(string $method): WithdrawMethod
    {
        try {
            return WithdrawMethod::from($method);
        } catch (ValueError) {
            throw new UnsupportedWithdrawMethodException();
        }
    }

    private function parseScheduleSafely(?string $schedule): ?DateTimeImmutable
    {
        if ($schedule === null || $schedule === '') {
            return null;
        }

        $scheduledFor = DateTimeImmutable::createFromFormat('Y-m-d H:i', $schedule);

        return $scheduledFor instanceof DateTimeImmutable ? $scheduledFor : null;
    }

    private function assertScheduleIsValid(?DateTimeImmutable $schedule): void
    {
        if ($schedule !== null && $schedule < new DateTimeImmutable()) {
            throw new InvalidWithdrawScheduleException();
        }
    }

    private function normalizeAmount(int|float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function assertSufficientBalance(Account $account, string $amount): void
    {
        if ($this->toCents((string) $account->balance) < $this->toCents($amount)) {
            throw new InsufficientBalanceException();
        }
    }

    private function validateMethodPayload(WithdrawMethod $method, array $data): void
    {
        match ($method) {
            WithdrawMethod::PIX => $this->assertPixEmailKey($data),
        };
    }

    private function assertPixEmailKey(array $data): void
    {
        try {
            $pixKeyType = PixKeyType::from((string) ($data['pix']['type'] ?? ''));
        } catch (ValueError) {
            throw new UnsupportedPixKeyTypeException();
        }

        if ($pixKeyType !== PixKeyType::EMAIL) {
            throw new UnsupportedPixKeyTypeException();
        }
    }

    private function deductAccountBalance(Account $account, string $amount): void
    {
        $newBalanceInCents = $this->toCents((string) $account->balance) - $this->toCents($amount);

        if ($newBalanceInCents < 0) {
            throw new InsufficientBalanceException();
        }

        $account->update([
            'balance' => number_format($newBalanceInCents / 100, 2, '.', ''),
        ]);
    }

    private function buildWithdrawData(
        string $withdrawId,
        string $accountId,
        string $method,
        string $amount,
        ?DateTimeImmutable $schedule,
        bool $done,
        bool $error,
        ?string $errorReason,
    ): array {
        return [
            'id' => $withdrawId,
            'account_id' => $accountId,
            'method' => $method,
            'amount' => $amount,
            'scheduled' => $schedule !== null,
            'scheduled_for' => $schedule?->format('Y-m-d H:i:s'),
            'done' => $done,
            'error' => $error,
            'error_reason' => $errorReason,
        ];
    }

    private function storeMethodData(WithdrawMethod $method, string $withdrawId, array $data): array
    {
        return match ($method) {
            WithdrawMethod::PIX => $this->repository->storePixData($this->buildPixData($withdrawId, $data)),
        };
    }

    private function buildPixData(string $withdrawId, array $data): array
    {
        return [
            'account_withdraw_id' => $withdrawId,
            'type' => $data['pix']['type'],
            'key' => $data['pix']['key'],
        ];
    }

    private function sendWithdrawNotification(array $data, string $amount, DateTimeImmutable $processedAt): void
    {
        $this->mailService->sendWithdrawExecutedEmail(
            recipientEmail: $data['pix']['key'],
            amount: $amount,
            pixType: $data['pix']['type'],
            pixKey: $data['pix']['key'],
            processedAt: $processedAt,
        );
    }

    private function resolveFailedMethod(?string $method): string
    {
        if ($method === null || $method === '') {
            return 'UNKNOWN';
        }

        return substr($method, 0, 50);
    }

    private function toCents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
