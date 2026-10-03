<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API справочника SWIFT/BIC: методы, возвращающие {@see ApiResponse}. Получить: $client->swift->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class SwiftRawResource extends AbstractResource
{
    /**
     * Как {@see SwiftResource::get()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $bic
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function get(string $bic, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/swift/' . self::segment($bic),
        ], $params);
    }

    /**
     * Как {@see SwiftResource::search()}, но возвращает данные вместе с HTTP-метаданными.
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
            'path' => '/api/swift/search',
            'query' => [
                'query' => $query,
            ],
            'optionalQuery' => ['limit'],
        ], $params);
    }

    /**
     * Как {@see SwiftResource::validate()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $bic
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function validate(string $bic, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/swift/validate/' . self::segment($bic),
        ], $params);
    }

    /**
     * Как {@see SwiftResource::getStats()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getStats(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/swift/stats',
        ], $params);
    }
}
