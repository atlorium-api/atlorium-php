<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API стандартизации адреса: методы, возвращающие {@see ApiResponse}. Получить: $client->addressstd->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class AddressStdRawResource extends AbstractResource
{
    /**
     * Как {@see AddressStdResource::standardize()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $address
     * @param array{region?: int, bind?: bool, geocode?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function standardize(string $address, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/addressstd',
            'query' => [
                'address' => $address,
            ],
            'optionalQuery' => ['region', 'bind', 'geocode'],
        ], $params);
    }
}
