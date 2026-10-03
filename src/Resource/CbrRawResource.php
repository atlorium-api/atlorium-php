<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API справочника БИК: методы, возвращающие {@see ApiResponse}. Получить: $client->cbr->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class CbrRawResource extends AbstractResource
{
    /**
     * Как {@see CbrResource::get()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $bik
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function get(string $bik, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/cbr/' . self::segment($bik),
        ], $params);
    }

    /**
     * Как {@see CbrResource::search()}, но возвращает данные вместе с HTTP-метаданными.
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
            'path' => '/api/cbr/search',
            'query' => [
                'query' => $query,
            ],
            'optionalQuery' => ['limit'],
        ], $params);
    }

    /**
     * Как {@see CbrResource::getStats()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getStats(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/cbr/stats',
        ], $params);
    }
}
