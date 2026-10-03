<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API BIN Lookup: методы, возвращающие {@see ApiResponse}. Получить: $client->bin->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class BinRawResource extends AbstractResource
{
    /**
     * Как {@see BinResource::lookup()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $bin
     * @param array{customerIp?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function lookup(string $bin, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/bin/' . self::segment($bin),
            'optionalQuery' => ['customerIp'],
        ], $params);
    }

    /**
     * Как {@see BinResource::validate()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $bin
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function validate(string $bin, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/bin/validate/' . self::segment($bin),
        ], $params);
    }
}
