<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API проверки ссылки: методы, возвращающие {@see ApiResponse}. Получить: $client->urlcheck->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class UrlCheckRawResource extends AbstractResource
{
    /**
     * Как {@see UrlCheckResource::check()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $url
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function check(string $url, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/urlcheck',
            'query' => [
                'url' => $url,
            ],
        ], $params);
    }

    /**
     * Как {@see UrlCheckResource::checkBatch()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string|list<string> $urls
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function checkBatch($urls, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/urlcheck/batch',
            'json' => self::toList($urls),
        ], $params);
    }
}
