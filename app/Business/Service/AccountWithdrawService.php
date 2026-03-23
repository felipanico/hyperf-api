<?php

declare(strict_types=1);

namespace App\Business\Service;

use App\Business\Repository\AccountWithdrawRepository;
use App\Business\Service\Withdraw\WithdrawExecutionService;
use App\Contract\WithdrawMethodServiceInterface;
use App\Enum\WithdrawMethod;
use App\Exception\AccountNotFoundException;
use App\Exception\InvalidWithdrawScheduleException;
use App\Model\Account;
use Carbon\Carbon;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;
use Psr\SimpleCache\CacheInterface;
use Throwable;

class AccountWithdrawService
{
    private const NEXT_PENDING_WITHDRAW_CACHE_KEY = 'cron:withdraw:next-pending-scheduled-for';

    public function __construct(
        private readonly AccountWithdrawRepository $repository,
        private readonly WithdrawExecutionService $withdrawExecutionService,
        private readonly CacheInterface $cache,
        private readonly ConfigInterface $config,
    ) {
    }

    public function store(array $data): array
    {
        $amount = $this->normalizeAmount($data['amount']);
        $schedule = $this->parseScheduleSafely($data['schedule'] ?? null);
        $timezone = $this->config->get('app.timezone', 'America/Sao_Paulo');
        $processedAt = Carbon::now($timezone);
        $method = $this->withdrawExecutionService->resolveMethod($data['method']);
        $methodService = $this->withdrawExecutionService->resolveMethodService($method);

        try {
            $result = $this->processWithdraw($data, $amount, $schedule, $method, $methodService);
        } catch (Throwable $throwable) {
            $this->storeFailedWithdrawAttempt($data, $amount, $schedule, $throwable);
            throw $throwable;
        }

        $this->handleWithdrawExecution($schedule, $methodService, $result, $amount, $processedAt);

        return $result;
    }

    private function processWithdraw(
        array $data,
        string $amount,
        ?Carbon $schedule,
        WithdrawMethod $method,
        WithdrawMethodServiceInterface $methodService,
    ): array {
        $account = $this->findAccount($data['account_id']);

        $this->assertScheduleIsValid($schedule);
        $this->withdrawExecutionService->assertSufficientBalance($account, $amount);
        $methodService->validatePayload($data);

        return Db::transaction(function () use ($account, $data, $method, $methodService, $schedule, $amount): array {
            $withdrawId = Str::uuid()->toString();
            $isScheduled = $schedule !== null;

            if (! $isScheduled) {
                $this->withdrawExecutionService->deductAccountBalance($account, $amount);
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

            $details = $methodService->storeData($withdrawId, $data);

            return [
                'withdraw' => $withdraw,
                'details' => $details,
                'notification_data' => $this->buildNotificationData($data, $withdrawId),
                'account' => $account->fresh()?->toArray(),
            ];
        });
    }

    private function handleWithdrawExecution(
        ?Carbon $schedule,
        WithdrawMethodServiceInterface $methodService,
        array $result,
        string $amount,
        Carbon $processedAt,
    ): void {
        if ($schedule !== null) {
            $this->refreshNextPendingWithdrawCache();
            return;
        }

        $this->withdrawExecutionService->sendNotification(
            $methodService,
            $result['notification_data'],
            $amount,
            $processedAt->toDateTimeImmutable(),
        );
    }

    private function buildNotificationData(array $data, string $withdrawId): array
    {
        return [
            ...$data,
            'account_withdraw_id' => $withdrawId,
        ];
    }

    private function storeFailedWithdrawAttempt(
        array $data,
        string $amount,
        ?Carbon $schedule,
        Throwable $throwable,
    ): void {
        try {
            $this->repository->storeWithDrawData($this->buildWithdrawData(
                withdrawId: Str::uuid()->toString(),
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

    private function parseScheduleSafely(?string $schedule): ?Carbon
    {
        if ($schedule === null || $schedule === '') {
            return null;
        }

        $timezone = $this->config->get('app.timezone', 'America/Sao_Paulo');

        return Carbon::createFromFormat('Y-m-d H:i', $schedule, $timezone);
    }

    private function assertScheduleIsValid(?Carbon $schedule): void
    {
        if ($schedule === null) {
            return;
        }

        $timezone = $this->config->get('app.timezone', 'America/Sao_Paulo');
        $now = Carbon::now($timezone);

        if ($schedule->lt($now)) {
            throw new InvalidWithdrawScheduleException();
        }
    }

    private function normalizeAmount(int|float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function buildWithdrawData(
        string $withdrawId,
        string $accountId,
        string $method,
        string $amount,
        ?Carbon $schedule,
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

    private function resolveFailedMethod(?string $method): string
    {
        if ($method === null || $method === '') {
            return 'UNKNOWN';
        }

        return substr($method, 0, 50);
    }

    private function refreshNextPendingWithdrawCache(): void
    {
        $nextPendingScheduledFor = $this->repository->getNextPendingScheduledFor();

        if ($nextPendingScheduledFor === null) {
            $this->cache->delete(self::NEXT_PENDING_WITHDRAW_CACHE_KEY);
            return;
        }

        $this->cache->set(self::NEXT_PENDING_WITHDRAW_CACHE_KEY, $nextPendingScheduledFor, 120);
    }
}
