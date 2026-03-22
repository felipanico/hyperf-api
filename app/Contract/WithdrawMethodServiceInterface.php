<?php

declare(strict_types=1);

namespace App\Contract;

use DateTimeImmutable;

interface WithdrawMethodServiceInterface
{
    public function validatePayload(array $data): void;

    public function storeData(string $withdrawId, array $data): array;

    public function sendNotification(array $data, string $amount, DateTimeImmutable $processedAt): void;
}
