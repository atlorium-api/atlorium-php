<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API валидации телефона: методы, возвращающие {@see ApiResponse}. Получить: $client->phone->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class PhoneRawResource extends AbstractResource
{
    /**
     * Как {@see PhoneResource::validate()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $number
     * @param array{countryCode?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function validate(string $number, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/phone/' . self::segment($number),
            'optionalQuery' => ['countryCode'],
        ], $params);
    }
}
