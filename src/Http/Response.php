<?php

declare(strict_types=1);

namespace Atlorium\Http;

/**
 * «Сырой» HTTP-ответ, который транспорт возвращает SDK.
 *
 * Имена заголовков хранятся в нижнем регистре, значения — списками строк.
 */
final class Response
{
    private int $statusCode;

    /** @var array<string, list<string>> */
    private array $headers;

    private string $body;

    /**
     * @param int                    $statusCode HTTP-статус
     * @param array<array-key, mixed> $headers    заголовки: «имя => значение» или «имя => [значения]»
     * @param string                 $body       тело ответа как есть
     */
    public function __construct(int $statusCode, array $headers = [], string $body = '')
    {
        $this->statusCode = $statusCode;
        $this->headers = Headers::normalize($headers);
        $this->body = $body;
    }

    /**
     * Ответ с JSON-телом — удобно для тестов на подменённом транспорте.
     *
     * @param mixed                  $data    данные, которые будут закодированы в JSON
     * @param array<array-key, mixed> $headers дополнительные заголовки
     */
    public static function json(int $statusCode, $data, array $headers = []): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if ($body === false) {
            throw new \InvalidArgumentException('Не удалось закодировать данные в JSON: ' . json_last_error_msg());
        }

        return new self($statusCode, $headers + ['Content-Type' => 'application/json; charset=utf-8'], $body);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, list<string>> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** Значение заголовка без учёта регистра; несколько значений склеиваются через «, ». */
    public function getHeader(string $name): ?string
    {
        return Headers::get($this->headers, $name);
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
