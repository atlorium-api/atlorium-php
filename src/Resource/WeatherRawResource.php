<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API погодных данных: методы, возвращающие {@see ApiResponse}. Получить: $client->weather->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class WeatherRawResource extends AbstractResource
{
    /**
     * Как {@see WeatherResource::get()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param float $latitude
     * @param float $longitude
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function get(float $latitude, float $longitude, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/weather',
            'query' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ], $params);
    }
}
