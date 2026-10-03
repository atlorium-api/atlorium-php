<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * ГАР/ФИАС API: методы, возвращающие {@see ApiResponse}. Получить: $client->gar->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class GarRawResource extends AbstractResource
{
    /**
     * Как {@see GarResource::search()}, но возвращает данные вместе с HTTP-метаданными.
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
            'path' => '/api/gar/search',
            'query' => [
                'query' => $query,
            ],
            'optionalQuery' => ['limit'],
        ], $params);
    }

    /**
     * Как {@see GarResource::suggest()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $query
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function suggest(string $query, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/gar/suggest',
            'query' => [
                'query' => $query,
            ],
            'optionalQuery' => ['limit'],
        ], $params);
    }

    /**
     * Как {@see GarResource::getObject()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $guid
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getObject(string $guid, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/gar/object/' . self::segment($guid),
        ], $params);
    }

    /**
     * Как {@see GarResource::getObjectById()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param int|string $objectId
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getObjectById($objectId, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/gar/object/id/' . self::segment($objectId),
        ], $params);
    }

    /**
     * Как {@see GarResource::getChildren()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param int|string $parentObjectId
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getChildren($parentObjectId, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/gar/children/' . self::segment($parentObjectId),
            'optionalQuery' => ['limit'],
        ], $params);
    }

    /**
     * Как {@see GarResource::getHierarchy()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param int|string $objectId
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getHierarchy($objectId, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/gar/hierarchy/' . self::segment($objectId),
        ], $params);
    }

    /**
     * Как {@see GarResource::getStats()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getStats(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/gar/stats',
        ], $params);
    }

    /**
     * Как {@see GarResource::listRegions()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function listRegions(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/gar/regions',
        ], $params);
    }

    /**
     * Как {@see GarResource::getRegionStats()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $regionCode
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getRegionStats(string $regionCode, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/gar/region/' . self::segment($regionCode) . '/stats',
        ], $params);
    }
}
