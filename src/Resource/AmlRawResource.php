<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API AML-скоринга: методы, возвращающие {@see ApiResponse}. Получить: $client->aml->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class AmlRawResource extends AbstractResource
{
    /**
     * Как {@see AmlResource::screen()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $walletAddress
     * @param string $blockchain
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function screen(string $walletAddress, string $blockchain, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/aml/screen',
            'body' => [
                'walletAddress' => $walletAddress,
                'blockchain' => $blockchain,
            ],
        ], $params);
    }

    /**
     * Как {@see AmlResource::validate()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $walletAddress
     * @param string $blockchain
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function validate(string $walletAddress, string $blockchain, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/aml/validate',
            'query' => [
                'walletAddress' => $walletAddress,
                'blockchain' => $blockchain,
            ],
        ], $params);
    }
}
