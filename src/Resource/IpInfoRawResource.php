<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API профиля IP: методы, возвращающие {@see ApiResponse}. Получить: $client->ipinfo->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class IpInfoRawResource extends AbstractResource
{
    /**
     * Как {@see IpInfoResource::lookup()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $ip
     * @param array{extended?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function lookup(string $ip, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/ipinfo/' . self::segment($ip),
            'optionalQuery' => ['extended'],
        ], $params);
    }
}
