<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API обратного геокодинга: методы, возвращающие {@see ApiResponse}. Получить: $client->geocodereverse->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class GeocodeReverseRawResource extends AbstractResource
{
    /**
     * Как {@see GeocodeReverseResource::lookup()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param float $lat
     * @param float $lon
     * @param array{includeNeighbors?: bool, neighborsSize?: int, includeAdmin?: bool, includeNearby?: bool, nearbyRadiusKm?: float, nearbySize?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function lookup(float $lat, float $lon, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/geocodereverse',
            'query' => [
                'lat' => $lat,
                'lon' => $lon,
            ],
            'optionalQuery' => ['includeNeighbors', 'neighborsSize', 'includeAdmin', 'includeNearby', 'nearbyRadiusKm', 'nearbySize'],
        ], $params);
    }

    /**
     * Как {@see GeocodeReverseResource::nearby()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param float $lat
     * @param float $lon
     * @param array{radiusKm?: float, size?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function nearby(float $lat, float $lon, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/geocodereverse/nearby',
            'query' => [
                'lat' => $lat,
                'lon' => $lon,
            ],
            'optionalQuery' => ['radiusKm', 'size'],
        ], $params);
    }
}
