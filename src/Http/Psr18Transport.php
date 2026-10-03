<?php

declare(strict_types=1);

namespace Atlorium\Http;

use Atlorium\Exception\ConnectionException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Транспорт поверх любого PSR-18 клиента: Guzzle, Symfony HttpClient и т.п.
 *
 *     $factory = new \GuzzleHttp\Psr7\HttpFactory();
 *     $client = new \Atlorium\Client(null, [
 *         'transport' => new Psr18Transport(new \GuzzleHttp\Client(['timeout' => 30]), $factory, $factory),
 *     ]);
 *
 * Таймаут PSR-18 не передаёт: настройте его в самом HTTP-клиенте. Нужны пакеты psr/http-client и
 * psr/http-factory (есть у всех совместимых клиентов).
 */
final class Psr18Transport implements TransportInterface
{
    private ClientInterface $client;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    public function send(Request $request): Response
    {
        $psrRequest = $this->requestFactory->createRequest($request->getMethod(), $request->getUrl());
        foreach ($request->getHeaders() as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }
        $body = $request->getBody();
        if ($body !== null) {
            $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($body));
        }

        try {
            $psrResponse = $this->client->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $error) {
            // Ответа нет вовсе (сеть, таймаут клиента). HTTP-ошибки PSR-18 клиент исключениями не бросает.
            throw new ConnectionException('Не удалось подключиться к API Atlorium: ' . $error->getMessage(), null, '', [], $error);
        }

        return new Response($psrResponse->getStatusCode(), $psrResponse->getHeaders(), (string) $psrResponse->getBody());
    }
}
