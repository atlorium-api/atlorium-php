<?php

declare(strict_types=1);

namespace Atlorium\Tests;

use Atlorium\Client;
use Atlorium\Exception\ConnectionException;
use Atlorium\Exception\NotFoundException;
use Atlorium\Http\Psr18Transport;
use Atlorium\Tests\Support\FakeClientException;
use Atlorium\Tests\Support\FakePsr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Psr18Transport с фабриками nyholm/psr7 и подменным PSR-18 клиентом (без сети).
 */
final class Psr18TransportTest extends TestCase
{
    private static function client(FakePsr18Client $http): Client
    {
        $factory = new Psr17Factory();

        return new Client('ak_test', ['transport' => new Psr18Transport($http, $factory, $factory), 'maxRetries' => 0]);
    }

    private static function jsonResponse(int $status, string $json): ResponseInterface
    {
        $factory = new Psr17Factory();

        return $factory->createResponse($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('X-Atlorium-Sandbox', 'true')
            ->withBody($factory->createStream($json));
    }

    public function testRequestIsConvertedAndResponseParsed(): void
    {
        $http = new FakePsr18Client(self::jsonResponse(200, '{"ok":true,"name":"Ромашка"}'));
        $raw = self::client($http)->send([
            'method' => 'POST',
            'path' => '/api/x',
            'query' => ['a' => 1, 'flag' => true],
            'body' => ['message' => 'Привет'],
        ]);

        self::assertSame(['ok' => true, 'name' => 'Ромашка'], $raw->getData());
        self::assertSame(200, $raw->getStatusCode());
        self::assertTrue($raw->isSandbox());

        $request = $http->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://atlorium.com/api/x?a=1&flag=true', (string) $request->getUri());
        self::assertSame('Bearer ak_test', $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('{"message":"Привет"}', (string) $request->getBody());
    }

    public function testGetHasNoBody(): void
    {
        $http = new FakePsr18Client(self::jsonResponse(200, '[]'));
        self::client($http)->send(['method' => 'GET', 'path' => '/api/y']);

        $request = $http->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('GET', $request->getMethod());
        self::assertSame('', (string) $request->getBody());
        self::assertFalse($request->hasHeader('Content-Type'));
    }

    public function testErrorStatusBecomesTypedException(): void
    {
        $http = new FakePsr18Client(self::jsonResponse(404, '{"error":"not_found","message":"Нет такой организации"}'));

        try {
            self::client($http)->send(['method' => 'GET', 'path' => '/api/egrul/0']);
            self::fail('Ожидалось исключение');
        } catch (NotFoundException $error) {
            self::assertSame('not_found', $error->getErrorCode());
            self::assertStringContainsString('Нет такой организации', $error->getMessage());
        }
    }

    public function testClientExceptionBecomesConnectionException(): void
    {
        $failure = new FakeClientException('сеть недоступна');
        $http = new FakePsr18Client($failure);

        try {
            self::client($http)->send(['method' => 'GET', 'path' => '/api/z']);
            self::fail('Ожидалось исключение');
        } catch (ConnectionException $error) {
            self::assertStringContainsString('сеть недоступна', $error->getMessage());
            self::assertSame($failure, $error->getPrevious());
            self::assertFalse($error->isRequestNotSent());
            self::assertNull($error->getStatusCode());
        }
    }
}
