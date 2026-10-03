<?php

declare(strict_types=1);

namespace Atlorium\Http;

/**
 * Готовый к отправке HTTP-запрос: то, что SDK передаёт транспорту.
 *
 * Объект неизменяемый. Полезен в тестах: по нему удобно проверять, что ушло на сервер.
 */
final class Request
{
    private string $method;

    private string $url;

    /** @var array<string, string> */
    private array $headers;

    private ?string $body;

    private float $timeout;

    /**
     * @param string                $method  HTTP-метод в верхнем регистре (GET, POST, DELETE)
     * @param string                $url     полный адрес вместе со строкой запроса
     * @param array<string, string> $headers заголовки «имя => значение»
     * @param string|null           $body    тело запроса (JSON) или null
     * @param float                 $timeout таймаут всего запроса, секунды
     */
    public function __construct(string $method, string $url, array $headers = [], ?string $body = null, float $timeout = 30.0)
    {
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->timeout = $timeout;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /** Полный адрес со строкой запроса. */
    public function getUrl(): string
    {
        return $this->url;
    }

    /** Путь без хоста и строки запроса, например «/api/egrul/7707083893». */
    public function getPath(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH);

        return is_string($path) ? $path : '';
    }

    /**
     * Разобранная строка запроса.
     *
     * @return array<array-key, mixed>
     */
    public function getQuery(): array
    {
        $query = parse_url($this->url, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $result);

        return $result;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** Значение заголовка без учёта регистра имени. */
    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Тело запроса, разобранное из JSON; null, если тела нет.
     *
     * @return mixed
     */
    public function getJson()
    {
        if ($this->body === null || $this->body === '') {
            return null;
        }

        return json_decode($this->body, true);
    }

    /** Таймаут всего запроса в секундах. */
    public function getTimeout(): float
    {
        return $this->timeout;
    }
}
