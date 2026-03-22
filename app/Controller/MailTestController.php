<?php

declare(strict_types=1);

namespace App\Controller;

use App\Business\Service\MailService;
use DateTimeImmutable;

class MailTestController extends AbstractController
{
    public function __construct(private readonly MailService $mailService)
    {
    }

    public function send(): array
    {
        $recipient = (string) $this->request->input('to', 'teste@mailhog.local');

        $this->mailService->sendWithdrawExecutedEmail(
            recipientEmail: $recipient,
            amount: '150.75',
            pixType: 'email',
            pixKey: $recipient,
            processedAt: new DateTimeImmutable(),
        );

        return [
            'message' => 'Test email sent.',
            'to' => $recipient,
        ];
    }
}
