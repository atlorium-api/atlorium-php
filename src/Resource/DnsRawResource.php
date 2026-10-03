<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API проверки DNS: методы, возвращающие {@see ApiResponse}. Получить: $client->dns->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class DnsRawResource extends AbstractResource
{
    /**
     * Как {@see DnsResource::lookup()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $domain
     * @param array{resolver?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function lookup(string $domain, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/dns/lookup',
            'query' => [
                'domain' => $domain,
            ],
            'optionalQuery' => ['resolver'],
        ], $params);
    }

    /**
     * Как {@see DnsResource::checkPropagation()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $domain
     * @param array{resolvers?: string|list<string>, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function checkPropagation(string $domain, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/dns/propagation',
            'query' => [
                'domain' => $domain,
            ],
            'optionalQuery' => ['resolvers'],
        ], $params);
    }
}
