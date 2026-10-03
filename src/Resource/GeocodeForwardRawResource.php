<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API прямого геокодинга: методы, возвращающие {@see ApiResponse}. Получить: $client->geocodeforward->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class GeocodeForwardRawResource extends AbstractResource
{
    /**
     * Как {@see GeocodeForwardResource::search()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $text
     * @param array{size?: int, exactOnly?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function search(string $text, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/geocodeforward',
            'query' => [
                'text' => $text,
            ],
            'optionalQuery' => ['size', 'exactOnly'],
        ], $params);
    }

    /**
     * Как {@see GeocodeForwardResource::searchStructured()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{address?: string, locality?: string, region?: string, postalCode?: string, size?: int, exactOnly?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function searchStructured(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/geocodeforward/structured',
            'optionalQuery' => ['address', 'locality', 'region', 'postalCode', 'size', 'exactOnly'],
        ], $params);
    }

    /**
     * Как {@see GeocodeForwardResource::searchBatch()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string|list<string> $queries
     * @param array{sizePerQuery?: int, exactOnly?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function searchBatch($queries, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/geocodeforward/batch',
            'body' => [
                'queries' => self::toList($queries),
            ],
            'optionalBody' => ['sizePerQuery', 'exactOnly'],
        ], $params);
    }

    /**
     * Как {@see GeocodeForwardResource::getPlace()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $placeId
     * @param array{includeBoundary?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getPlace(string $placeId, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/geocodeforward/place',
            'query' => [
                'placeId' => $placeId,
            ],
            'optionalQuery' => ['includeBoundary'],
        ], $params);
    }

    /**
     * Как {@see GeocodeForwardResource::searchPostcode()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $code
     * @param array{size?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function searchPostcode(string $code, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/geocodeforward/postcode',
            'query' => [
                'code' => $code,
            ],
            'optionalQuery' => ['size'],
        ], $params);
    }
}
