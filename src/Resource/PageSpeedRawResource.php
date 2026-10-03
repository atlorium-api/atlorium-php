<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API аудита сайта: методы, возвращающие {@see ApiResponse}. Получить: $client->pagespeed->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class PageSpeedRawResource extends AbstractResource
{
    /**
     * Как {@see PageSpeedResource::audit()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $url
     * @param array{strategy?: 'mobile'|'desktop', forceRefresh?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function audit(string $url, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/pagespeed',
            'query' => [
                'url' => $url,
            ],
            'optionalQuery' => ['strategy', 'forceRefresh'],
            'defaultTimeout' => 120.0,
        ], $params);
    }

    /**
     * Как {@see PageSpeedResource::auditBatch()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string|list<string> $urls
     * @param array{strategy?: 'mobile'|'desktop', timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function auditBatch($urls, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/pagespeed/batch',
            'optionalQuery' => ['strategy'],
            'json' => self::toList($urls),
            'defaultTimeout' => 600.0,
        ], $params);
    }
}
