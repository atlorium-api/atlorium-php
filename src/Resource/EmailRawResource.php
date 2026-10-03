<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API проверки почты: методы, возвращающие {@see ApiResponse}. Получить: $client->email->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class EmailRawResource extends AbstractResource
{
    /**
     * Как {@see EmailResource::validate()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $email
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function validate(string $email, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/emailvalidation',
            'query' => [
                'email' => $email,
            ],
        ], $params);
    }
}
