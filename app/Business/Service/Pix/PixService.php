<?php

declare(strict_types=1);

namespace App\Business\Service\Pix;

use App\Business\Repository\Pix\PixRepository;
use App\Business\Service\MailService;
use App\Contract\WithdrawMethodServiceInterface;
use App\Enum\PixKeyType;
use App\Exception\UnsupportedPixKeyTypeException;
use DateTimeImmutable;
use ValueError;

class PixService implements WithdrawMethodServiceInterface
{
    public function __construct(
        private readonly PixRepository $repository,
        private readonly MailService $mailService,
    ) {
    }

    public function validatePayload(array $data): void
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

    public function storeData(string $withdrawId, array $data): array
    {
        return $this->repository->storePixData([
            'account_withdraw_id' => $withdrawId,
            'type' => $data['pix']['type'],
            'key' => $data['pix']['key'],
        ]);
    }

    public function sendNotification(array $data, string $amount, DateTimeImmutable $processedAt): void
    {
        $this->mailService->sendWithdrawExecutedEmail(
            withdrawId: (string) ($data['account_withdraw_id'] ?? ''),
            recipientEmail: $data['pix']['key'],
            amount: $amount,
            pixType: $data['pix']['type'],
            pixKey: $data['pix']['key'],
            processedAt: $processedAt,
        );
    }
}
