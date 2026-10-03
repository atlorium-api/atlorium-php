<?php

declare(strict_types=1);

namespace Atlorium;

use Atlorium\Http\Headers;
use Atlorium\Http\Response;

/**
 * Данные ответа вместе с HTTP-метаданными.
 *
 * Обычные методы возвращают сразу данные. Чтобы получить ещё статус и заголовки, вызовите тот же
 * метод через withRawResponse():
 *
 *     $raw = $client->testdata->withRawResponse()->generate(['count' => 5]);
 *     echo $raw->getStatusCode(), $raw->getHeader('X-Atlorium-Seed');
 *     $data = $raw->getData();
 */
final class ApiResponse
{
    /** @var mixed */
    private $data;

    private Response $response;

    /** @param mixed $data */
    public function __construct($data, Response $response)
    {
        $this->data = $data;
        $this->response = $response;
    }

    /**
     * Разобранное тело ответа — то же, что возвращает обычный вызов метода.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /** @return array<string, list<string>> */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /** Значение заголовка без учёта регистра имени. */
    public function getHeader(string $name): ?string
    {
        return $this->response->getHeader($name);
    }

    /**
     * true, если сервер пометил ответ заголовком X-Atlorium-Sandbox: true. У полностью локальных
     * сервисов (например, генератора тестовых данных) заголовка нет даже на демо-ключе — ответ
     * там настоящий.
     */
    public function isSandbox(): bool
    {
        return strtolower(trim((string) $this->getHeader('X-Atlorium-Sandbox'))) === 'true';
    }

    /** Идентификатор запроса из X-Request-Id, если сервер его прислал. */
    public function getRequestId(): ?string
    {
        return $this->getHeader('X-Request-Id') ?? $this->getHeader('Request-Id');
    }

    /** Значение Retry-After в секундах, если заголовок есть. */
    public function getRetryAfter(): ?float
    {
        return Headers::parseRetryAfter($this->getHeader('Retry-After'));
    }

    /**
     * Остатки лимита из заголовков X-RateLimit-* / RateLimit-* или null, если сервер их не отдаёт.
     *
     * @return array{limit: int|null, remaining: int|null, reset: float|null}|null
     */
    public function getRateLimit(): ?array
    {
        foreach (['X-RateLimit-', 'RateLimit-'] as $prefix) {
            $limit = $this->numberHeader($prefix . 'Limit');
            $remaining = $this->numberHeader($prefix . 'Remaining');
            $reset = $this->numberHeader($prefix . 'Reset');
            if ($limit !== null || $remaining !== null || $reset !== null) {
                return [
                    'limit' => $limit === null ? null : (int) $limit,
                    'remaining' => $remaining === null ? null : (int) $remaining,
                    'reset' => $reset,
                ];
            }
        }

        return null;
    }

    /** Исходный HTTP-ответ. */
    public function getRawResponse(): Response
    {
        return $this->response;
    }

    private function numberHeader(string $name): ?float
    {
        $value = $this->getHeader($name);

        return $value !== null && is_numeric(trim($value)) ? (float) trim($value) : null;
    }
}
