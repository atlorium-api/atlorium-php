<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API разбора Cron-выражений: методы, возвращающие {@see ApiResponse}. Получить: $client->cron->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class CronRawResource extends AbstractResource
{
    /**
     * Как {@see CronResource::evaluate()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $expression
     * @param array{timeZoneId?: string, fromUtc?: \DateTimeInterface|string, take?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function evaluate(string $expression, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/cron/evaluate',
            'body' => [
                'expression' => $expression,
            ],
            'optionalBody' => ['timeZoneId', 'fromUtc', 'take'],
        ], $params);
    }

    /**
     * Как {@see CronResource::build()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{template?: int, interval?: int, timeOfDay?: string, dayOfMonth?: int, weekDays?: list<int>, includeSeconds?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function build(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/cron/build',
            'body' => [],
            'optionalBody' => ['template', 'interval', 'timeOfDay', 'dayOfMonth', 'weekDays', 'includeSeconds'],
        ], $params);
    }
}
