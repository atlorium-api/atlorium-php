<?php

declare(strict_types=1);

namespace Atlorium\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Простейший PSR-18 клиент для тестов: запоминает запрос и отдаёт заданный ответ либо бросает
 * заданное исключение. В сеть не ходит.
 */
final class FakePsr18Client implements ClientInterface
{
    /** @var ResponseInterface|FakeClientException */
    private $result;

    private ?RequestInterface $lastRequest = null;

    /** @param ResponseInterface|FakeClientException $result */
    public function __construct($result)
    {
        $this->result = $result;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        if ($this->result instanceof ResponseInterface) {
            return $this->result;
        }

        throw $this->result;
    }

    public function getLastRequest(): ?RequestInterface
    {
        return $this->lastRequest;
    }
}
