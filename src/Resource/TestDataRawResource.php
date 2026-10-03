<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API генератора тестовых данных РФ: методы, возвращающие {@see ApiResponse}. Получить: $client->testdata->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class TestDataRawResource extends AbstractResource
{
    /**
     * Как {@see TestDataResource::generate()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{count?: int, fields?: string|list<string>, seed?: int, gender?: 'any'|'male'|'female', format?: 'json'|'csv', timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function generate(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/testdata',
            'optionalQuery' => ['count', 'fields', 'seed', 'gender', 'format'],
            'responseType' => 'auto',
        ], $params);
    }

    /**
     * Как {@see TestDataResource::generateWithBody()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{count?: int, seed?: int, fields?: string|list<string>, gender?: 'any'|'male'|'female', format?: 'json'|'csv', timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function generateWithBody(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/testdata',
            'body' => [],
            'optionalBody' => ['count', 'seed', 'fields', 'gender', 'format'],
            'bodyLists' => ['fields'],
            'responseType' => 'auto',
        ], $params);
    }

    /**
     * Как {@see TestDataResource::listFields()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function listFields(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/testdata/fields',
        ], $params);
    }
}
