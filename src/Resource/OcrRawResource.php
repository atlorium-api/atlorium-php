<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;
use Atlorium\ImageInput;

/**
 * API распознавания текста с изображения: методы, возвращающие {@see ApiResponse}. Получить: $client->ocr->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class OcrRawResource extends AbstractResource
{
    /**
     * Как {@see OcrResource::recognize()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param ImageInput|\SplFileInfo|resource|string $image
     * @param array{mode?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function recognize($image, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/ocr/image-to-text',
            'body' => [
                'image' => ImageInput::encode($image),
            ],
            'optionalBody' => ['mode'],
            'defaultTimeout' => 120.0,
        ], $params);
    }
}
