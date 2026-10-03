<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\Exception\AtloriumException;

/**
 * API калькулятора подсетей: методы, возвращающие {@see ApiResponse}. Получить: $client->cidr->withRawResponse().
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class CidrRawResource extends AbstractResource
{
    /**
     * Как {@see CidrResource::calculate()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $ip
     * @param int $mask
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function calculate(string $ip, int $mask, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/cidr/calculate',
            'query' => [
                'ip' => $ip,
                'mask' => $mask,
            ],
        ], $params);
    }

    /**
     * Как {@see CidrResource::split()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $cidr
     * @param int $newMask
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function split(string $cidr, int $newMask, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/cidr/subnets',
            'query' => [
                'cidr' => $cidr,
                'newMask' => $newMask,
            ],
        ], $params);
    }

    /**
     * Как {@see CidrResource::check()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string $cidr
     * @param string $ip
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function check(string $cidr, string $ip, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'GET',
            'path' => '/api/cidr/check',
            'query' => [
                'cidr' => $cidr,
                'ip' => $ip,
            ],
        ], $params);
    }

    /**
     * Как {@see CidrResource::supernet()}, но возвращает данные вместе с HTTP-метаданными.
     *
     * @param string|list<string> $inputs
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *
     * @throws AtloriumException
     */
    public function supernet($inputs, array $params = []): ApiResponse
    {
        return $this->client->send([
            'method' => 'POST',
            'path' => '/api/cidr/supernet',
            'body' => [
                'inputs' => self::toList($inputs),
            ],
        ], $params);
    }
}
