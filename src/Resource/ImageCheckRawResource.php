<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;
use Atlorium\ImageInput;

/**
 * API анализа изображения: методы, возвращающие {@see ApiResponse}. Получить: $client->imagecheck->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class ImageCheckRawResource extends AbstractResource
{
    /**
     * Как {@see ImageCheckResource::analyze()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param ImageInput|\SplFileInfo|resource|string $image
     * @param array{includeText?: bool, includeWebSearch?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function analyze($image, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/imagecheck',
            'body' => [
                'image' => ImageInput::encode($image),
            ],
            'optionalBody' => ['includeText', 'includeWebSearch'],
            'defaultTimeout' => 60.0,
        ], $params);
    }
}
