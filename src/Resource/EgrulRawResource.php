<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API ЕГРЮЛ/ЕГРИП: методы, возвращающие {@see ApiResponse}. Получить: $client->egrul->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class EgrulRawResource extends AbstractResource
{
    /**
     * Как {@see EgrulResource::get()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $query
     * @param array{extended?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function get(string $query, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/egrul/' . self::segment($query),
            'optionalQuery' => ['extended'],
        ], $params);
    }

    /**
     * Как {@see EgrulResource::search()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $query
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function search(string $query, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/egrul/search',
            'query' => [
                'query' => $query,
            ],
            'optionalQuery' => ['limit'],
        ], $params);
    }

    /**
     * Как {@see EgrulResource::getExcerpt()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $query
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getExcerpt(string $query, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/egrul/' . self::segment($query) . '/excerpt',
            'responseType' => 'bytes',
        ], $params);
    }
}
