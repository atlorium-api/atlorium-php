<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API проверки SSL-сертификата: методы, возвращающие {@see ApiResponse}. Получить: $client->certificate->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class CertificateRawResource extends AbstractResource
{
    /**
     * Как {@see CertificateResource::check()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $host
     * @param array{port?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function check(string $host, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/certificate',
            'query' => [
                'host' => $host,
            ],
            'optionalQuery' => ['port'],
        ], $params);
    }

    /**
     * Как {@see CertificateResource::checkUrl()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $url
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function checkUrl(string $url, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/certificate/url',
            'query' => [
                'url' => $url,
            ],
        ], $params);
    }

    /**
     * Как {@see CertificateResource::checkBatch()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string|list<string> $hosts
     * @param array{port?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function checkBatch($hosts, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/certificate/batch',
            'query' => [
                'hosts' => $hosts,
            ],
            'optionalQuery' => ['port'],
        ], $params);
    }
}
