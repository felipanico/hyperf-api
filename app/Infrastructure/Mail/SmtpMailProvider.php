<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Contract\MailProviderInterface;
use App\DTO\MailMessage;
use App\Exception\MailDeliveryException;
use Hyperf\Contract\ConfigInterface;

class SmtpMailProvider implements MailProviderInterface
{
    public function __construct(private readonly ConfigInterface $config)
    {
    }

    public function send(MailMessage $message): void
    {
        $socket = $this->connect();

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO localhost', 250);
            $this->command($socket, sprintf('MAIL FROM:<%s>', $message->from), 250);
            $this->command($socket, sprintf('RCPT TO:<%s>', $message->to), 250);
            $this->command($socket, 'DATA', 354);
            $this->write($socket, $this->buildMessage($message));
            $this->expect($socket, 250);
            $this->command($socket, 'QUIT', 221);
        } finally {
            fclose($socket);
        }
    }

    private function connect()
    {
        $host = (string) $this->config->get('mail.host', '127.0.0.1');
        $port = (int) $this->config->get('mail.port', 1025);
        $timeout = (float) $this->config->get('mail.timeout', 5.0);

        $socket = @stream_socket_client(
            sprintf('tcp://%s:%d', $host, $port),
            $errorCode,
            $errorMessage,
            $timeout
        );

        if (! is_resource($socket)) {
            throw new MailDeliveryException(sprintf('Unable to connect to mail server: %s', $errorMessage ?: 'unknown error'));
        }

        stream_set_timeout($socket, (int) ceil($timeout));

        return $socket;
    }

    private function buildMessage(MailMessage $message): string
    {
        $headers = [
            sprintf('From: %s', $message->from),
            sprintf('To: %s', $message->to),
            sprintf('Subject: %s', $message->subject),
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return implode("\r\n", $headers)
            . "\r\n\r\n"
            . $this->escapeBody($message->text)
            . "\r\n.\r\n";
    }

    private function escapeBody(string $body): string
    {
        $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $normalizedBody);

        return implode("\r\n", array_map(static function (string $line): string {
            return str_starts_with($line, '.') ? '.' . $line : $line;
        }, $lines));
    }

    private function command($socket, string $command, int $expectedCode): void
    {
        $this->write($socket, $command . "\r\n");
        $this->expect($socket, $expectedCode);
    }

    private function write($socket, string $content): void
    {
        if (fwrite($socket, $content) === false) {
            throw new MailDeliveryException('Unable to write to mail server socket.');
        }
    }

    private function expect($socket, int $expectedCode): void
    {
        $response = '';

        while (($line = fgets($socket)) !== false) {
            $response .= $line;

            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if (! str_starts_with($response, (string) $expectedCode)) {
            throw new MailDeliveryException(sprintf('Unexpected mail server response: %s', trim($response)));
        }
    }
}
