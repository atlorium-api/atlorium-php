<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API ИИ-чата: методы, возвращающие {@see ApiResponse}. Получить: $client->aichat->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class AiChatRawResource extends AbstractResource
{
    /**
     * Как {@see AiChatResource::send()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $message
     * @param array{sessionId?: string, model?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function send(string $message, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/aichat/send',
            'body' => [
                'message' => $message,
            ],
            'optionalBody' => ['sessionId', 'model'],
            'defaultTimeout' => 660.0,
        ], $params);
    }

    /**
     * Как {@see AiChatResource::getSession()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $sessionId
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function getSession(string $sessionId, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/aichat/session/' . self::segment($sessionId),
        ], $params);
    }

    /**
     * Как {@see AiChatResource::deleteSession()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $sessionId
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function deleteSession(string $sessionId, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'DELETE',
            'path' => '/api/aichat/session/' . self::segment($sessionId),
            'responseType' => 'auto',
        ], $params);
    }

    /**
     * Как {@see AiChatResource::listModels()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function listModels(array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/aichat/models',
        ], $params);
    }
}
