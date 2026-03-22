<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Exception\Handler;

use App\Exception\BusinessException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        if ($throwable instanceof ValidationException) {
            return $this->buildJsonResponse(
                $response,
                422,
                [
                    'message' => 'Validation failed.',
                    'errors' => $throwable->errors(),
                ]
            );
        }

        if ($throwable instanceof BusinessException) {
            return $this->buildJsonResponse(
                $response,
                $this->resolveBusinessStatusCode($throwable),
                [
                    'message' => $throwable->getMessage(),
                ]
            );
        }

        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());

        return $this->buildJsonResponse(
            $response,
            500,
            [
                'message' => 'Internal Server Error.',
            ]
        );
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    private function buildJsonResponse(ResponseInterface $response, int $status, array $payload): ResponseInterface
    {
        return $response
            ->withHeader('Server', 'Hyperf')
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status)
            ->withBody(new SwooleStream((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    private function resolveBusinessStatusCode(BusinessException $exception): int
    {
        $status = $exception->getCode();

        if ($status >= 400 && $status < 600) {
            return $status;
        }

        return 422;
    }
}
