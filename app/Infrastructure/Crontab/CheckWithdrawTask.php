<?php

declare(strict_types=1);

namespace App\Infrastructure\Crontab;

use App\Business\Repository\AccountWithdrawRepository;
use App\Business\Service\Withdraw\WithdrawExecutionService;
use Hyperf\Contract\ConfigInterface;
use App\Model\Account;
use App\Model\AccountWithdraw;
use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\SimpleCache\CacheInterface;
use Carbon\Carbon;
use Throwable;

class CheckWithdrawTask
{
    private const NEXT_PENDING_WITHDRAW_CACHE_KEY = 'cron:withdraw:next-pending-scheduled-for';

    public function __construct(
        private readonly LoggerFactory $loggerFactory,
        private readonly WithdrawExecutionService $withdrawExecutionService,
        private readonly AccountWithdrawRepository $withdrawRepository,
        private readonly CacheInterface $cache,
        private readonly ConfigInterface $config,
    ) {
    }

    public function execute(): void
    {
        $logger = $this->loggerFactory->get('cron');
        $logger->debug('Executed Cron: ' . date('Y-m-d H:i:s'));

        if (! $this->shouldScanPendingWithdraws()) {
            $logger->debug('Cron ignored: next scheduled withdrawal has not yet expired.');
            return;
        }

        $timeZone = $this->config->get('app.timezone', 'America/Sao_Paulo');

        $dateTime = Carbon::now($timeZone)->toDateTimeString();

        $pendingWithdraws = $this->withdrawRepository->getPendingWithDraws($dateTime);

        foreach ($pendingWithdraws as $withdraw) {
            try {
                $this->processScheduledWithdraw($withdraw);
            } catch (Throwable $throwable) {
                $this->markWithdrawAsFailed($withdraw, $throwable->getMessage());
                $logger->error(sprintf(
                    'Error processing scheduled withdrawal. %s: %s',
                    (string) $withdraw->id,
                    $throwable->getMessage()
                ));
            }
        }

        $this->refreshNextPendingWithdrawCache();

        $this->sendHeartBeat();
    }

    private function processScheduledWithdraw(AccountWithdraw $withdraw): void
    {
        $method = $this->withdrawExecutionService->resolveMethod($withdraw->method);
        $methodService = $this->withdrawExecutionService->resolveMethodService($method);
        $payload = $this->buildPayload($withdraw);
        $amount = (string) $withdraw->amount;

        $methodService->validatePayload($payload);

        $processed = Db::transaction(function () use ($withdraw, $amount): bool {
            $lockedWithdraw = $this->withdrawRepository->getAndLockWithdrawPix($withdraw->id);

            if (! $lockedWithdraw instanceof AccountWithdraw || $lockedWithdraw->done) {
                return false;
            }

            $account = $this->withdrawRepository->getAndLockAccount($lockedWithdraw->account_id);

            if (! $account instanceof Account) {
                $this->markWithdrawAsFailed($lockedWithdraw, 'Account not found.');
                return false;
            }

            $this->withdrawExecutionService->assertSufficientBalance($account, $amount);
            $this->withdrawExecutionService->deductAccountBalance($account, $amount);

            $lockedWithdraw->update([
                'done' => true,
                'error' => false,
                'error_reason' => null,
            ]);

            return true;
        });

        if (! $processed) {
            return;
        }

        $this->withdrawExecutionService->sendNotification($methodService, $payload, $amount, new DateTimeImmutable());
    }

    private function buildPayload(AccountWithdraw $withdraw): array
    {
        return [
            'account_id' => (string) $withdraw->account_id,
            'account_withdraw_id' => (string) $withdraw->id,
            'method' => (string) $withdraw->method,
            'amount' => (string) $withdraw->amount,
            'schedule' => $withdraw->scheduled_for?->format('Y-m-d H:i'),
            'pix' => [
                'type' => (string) $withdraw->pix?->type,
                'key' => (string) $withdraw->pix?->key,
            ],
        ];
    }

    private function markWithdrawAsFailed(AccountWithdraw $withdraw, string $reason): void
    {
        $withdraw->update([
            'done' => true,
            'error' => true,
            'error_reason' => $reason,
        ]);
    }

    private function shouldScanPendingWithdraws(): bool
    {
        $nextPendingScheduledFor = $this->cache->get(self::NEXT_PENDING_WITHDRAW_CACHE_KEY);

        if (! is_string($nextPendingScheduledFor) || $nextPendingScheduledFor === '') {
            return true;
        }

        return $nextPendingScheduledFor <= date('Y-m-d H:i:s');
    }

    private function refreshNextPendingWithdrawCache(): void
    {
        $nextPendingScheduledFor = $this->withdrawRepository->getNextPendingScheduledFor();

        if ($nextPendingScheduledFor === null) {
            $this->cache->delete(self::NEXT_PENDING_WITHDRAW_CACHE_KEY);
            return;
        }

        $this->cache->set(self::NEXT_PENDING_WITHDRAW_CACHE_KEY, $nextPendingScheduledFor, 120);
    }

    private function sendHeartBeat() : void
    {
        $host = $this->config->get('observability_host');
        $token = $this->config->get('observability_token');
        
        try {
            file_get_contents($host . "/api/push/{$token}?status=up");
        } catch (Throwable $e) {
            error_log('failed to send heartBeat: ' . serialize($e->getMessage()));
        }
    }
}
