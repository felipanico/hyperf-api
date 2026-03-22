<?php

declare(strict_types=1);

namespace App\Business\Service;

use App\Contract\MailProviderInterface;
use App\DTO\MailMessage;
use DateTimeImmutable;
use Hyperf\Contract\ConfigInterface;

class MailService
{
    public function __construct(
        private readonly MailProviderInterface $provider,
        private readonly ConfigInterface $config,
    ) {
    }

    public function sendWithdrawExecutedEmail(
        string $recipientEmail,
        string $amount,
        string $pixType,
        string $pixKey,
        DateTimeImmutable $processedAt,
    ): void {
        $message = new MailMessage(
            from: (string) $this->config->get('mail.from.address', 'no-reply@api-pix.local'),
            to: $recipientEmail,
            subject: 'Withdraw executed',
            text: $this->buildWithdrawExecutedBody($amount, $pixType, $pixKey, $processedAt),
        );

        $this->provider->send($message);
    }

    private function buildWithdrawExecutedBody(
        string $amount,
        string $pixType,
        string $pixKey,
        DateTimeImmutable $processedAt,
    ): string {
        return implode("\n", [
            'Your withdraw was successfully executed.',
            sprintf('Date and time: %s', $processedAt->format('Y-m-d H:i:s')),
            sprintf('Amount: %s', $amount),
            sprintf('PIX type: %s', $pixType),
            sprintf('PIX key: %s', $pixKey),
        ]);
    }
}
