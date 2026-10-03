<?php

declare(strict_types=1);

namespace Atlorium\Tests;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Client;
use Atlorium\Exception\AtloriumException;
use Atlorium\Exception\AuthenticationException;
use Atlorium\Exception\ConnectionException;
use Atlorium\Exception\InsufficientCreditsException;
use Atlorium\Exception\NotFoundException;
use Atlorium\Exception\PermissionDeniedException;
use Atlorium\Exception\RateLimitException;
use Atlorium\Exception\ServerException;
use Atlorium\Exception\ServiceUnavailableException;
use Atlorium\Exception\TimeoutException;
use Atlorium\Exception\ValidationException;
use Atlorium\Http\MockTransport;
use Atlorium\Http\Request;
use Atlorium\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Тесты транспортного ядра на MockTransport.
 */
final class ClientTest extends TestCase
{
    /** @var list<float> */
    private array $sleeps = [];

    protected function tearDown(): void
    {
        putenv('ATLORIUM_API_KEY');
        putenv('ATLORIUM_BASE_URL');
        unset($_ENV['ATLORIUM_API_KEY'], $_SERVER['ATLORIUM_API_KEY'], $_ENV['ATLORIUM_BASE_URL'], $_SERVER['ATLORIUM_BASE_URL']);
    }

    /** @param array<string, mixed> $options */
    private function client(MockTransport $transport, array $options = []): Client
    {
        $this->sleeps = [];

        return new Client('ak_test', $options + [
            'transport' => $transport,
            'maxRetries' => 0,
            'sleeper' => function (float $seconds): void {
                $this->sleeps[] = $seconds;
            },
        ]);
    }

    /** @param array<string, mixed> $query */
    private static function get(Client $client, array $query = []): ApiResponse
    {
        return $client->send(['method' => 'GET', 'path' => '/api/test', 'query' => $query]);
    }

    public function testApiKeyResolution(): void
    {
        $transport = new MockTransport();
        putenv('ATLORIUM_API_KEY=');
        self::assertTrue((new Client(null, ['transport' => $transport]))->isSandbox());
        putenv('ATLORIUM_API_KEY=ak_live_env');
        self::assertFalse((new Client(null, ['transport' => $transport]))->isSandbox());
        self::assertTrue((new Client(BaseClient::SANDBOX_API_KEY, ['transport' => $transport]))->isSandbox());
    }

    public function testKeyIsHiddenInDebugOutputAndErrors(): void
    {
        $transport = new MockTransport(Response::json(401, ['message' => 'нет']));
        $client = new Client('ak_live_secret_value', ['transport' => $transport, 'maxRetries' => 0]);
        self::assertStringNotContainsString('ak_live_secret_value', print_r($client, true));

        try {
            self::get($client);
            self::fail('Ожидалось исключение');
        } catch (AuthenticationException $error) {
            self::assertStringNotContainsString('ak_live_secret_value', $error->getMessage());
            self::assertStringNotContainsString('ak_live_secret_value', (string) $error);
        }
    }

    public function testBaseUrlFromEnvironmentWithoutTrailingSlash(): void
    {
        putenv('ATLORIUM_BASE_URL=https://example.test/');
        $transport = new MockTransport(Response::json(200, []));
        self::get(new Client(null, ['transport' => $transport]));
        self::assertSame('https://example.test/api/test', $transport->getLastRequest()->getUrl());
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidOptions(): iterable
    {
        yield 'неизвестная настройка' => [['timeot' => 5]];
        yield 'отрицательные повторы' => [['maxRetries' => -1]];
        yield 'отрицательная пауза перед повтором' => [['maxRetryDelay' => -1]];
        yield 'нулевой таймаут' => [['timeout' => 0]];
        yield 'таймаут строкой' => [['timeout' => '30']];
        yield 'транспорт не того типа' => [['transport' => new \stdClass()]];
    }

    /**
     * @dataProvider invalidOptions
     *
     * @param array<string, mixed> $options
     */
    public function testInvalidClientOptions(array $options): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client(null, $options + ['transport' => new MockTransport()]);
    }

    public function testHeadersAndQuerySerialization(): void
    {
        $transport = new MockTransport(Response::json(200, ['ok' => true]));
        self::get($this->client($transport), [
            'flag' => true,
            'off' => false,
            'skip' => null,
            'items' => ['a', 'b'],
            'n' => 5,
            'x' => 55.75,
            'at' => new \DateTimeImmutable('2026-01-02T03:04:05+00:00'),
        ]);

        $request = $transport->getLastRequest();
        self::assertSame('Bearer ak_test', $request->getHeader('Authorization'));
        self::assertSame('application/json', $request->getHeader('Accept'));
        self::assertStringStartsWith('atlorium-php/' . BaseClient::VERSION . ' (php/', (string) $request->getHeader('User-Agent'));
        self::assertSame(
            'https://atlorium.test/api/test?flag=true&off=false&items=a%2Cb&n=5&x=55.75&at=2026-01-02T03%3A04%3A05%2B00%3A00',
            str_replace('https://atlorium.com', 'https://atlorium.test', $request->getUrl())
        );
        self::assertNull($request->getBody());
    }

    public function testFloatsDoNotDependOnLocale(): void
    {
        // Битрикс ставит русскую локаль; в PHP 7.4 от неё зависит (string) 55.75 — «55,75».
        $previousLocale = setlocale(LC_NUMERIC, '0');
        $commaLocale = setlocale(LC_NUMERIC, 'ru_RU.UTF-8', 'ru_RU.utf8', 'ru_RU', 'de_DE.UTF-8', 'de_DE.utf8', 'de_DE', 'Russian');

        try {
            $decimalPoint = localeconv()['decimal_point'];
            if ($commaLocale === false || $decimalPoint !== ',') {
                self::markTestSkipped('В системе нет локали с десятичной запятой (ru_RU, de_DE).');
            }
            self::assertSame('55.75', BaseClient::serializeQueryValue(55.75));
            self::assertSame('-0.5', BaseClient::serializeQueryValue(-0.5));
            self::assertSame('1', BaseClient::serializeQueryValue(1.0));
        } finally {
            setlocale(LC_NUMERIC, $previousLocale === false ? 'C' : $previousLocale);
        }
    }

    public function testJsonBodyIsUtf8(): void
    {
        $transport = new MockTransport(Response::json(200, []));
        $this->client($transport)->send(['method' => 'POST', 'path' => '/api/x', 'body' => ['message' => 'Привет']]);
        $request = $transport->getLastRequest();
        self::assertSame('{"message":"Привет"}', $request->getBody());
        self::assertSame('application/json', $request->getHeader('Content-Type'));
    }

    public function testEmptyBodyIsJsonObject(): void
    {
        $transport = new MockTransport(Response::json(200, []));
        $this->client($transport)->send(['method' => 'POST', 'path' => '/api/x', 'body' => []]);
        self::assertSame('{}', $transport->getLastRequest()->getBody());
    }

    public function testNonUtf8BodyGivesClearError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UTF-8');
        $this->client(new MockTransport())->send(['method' => 'POST', 'path' => '/api/x', 'body' => ['message' => "\xCF\xF0\xE8"]]);
    }

    public function testUnknownParameterIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('limt');
        $this->client(new MockTransport())->send(['method' => 'GET', 'path' => '/a', 'optionalQuery' => ['limit']], ['limt' => 5]);
    }

    public function testCallHeadersDoNotOverrideAuthorization(): void
    {
        $transport = new MockTransport(Response::json(200, []));
        $client = $this->client($transport, ['headers' => ['X-App' => 'crm']]);
        $client->send(['method' => 'GET', 'path' => '/a'], ['headers' => ['X-Trace' => '1', 'Authorization' => 'Bearer other']]);
        $request = $transport->getLastRequest();
        self::assertSame('crm', $request->getHeader('X-App'));
        self::assertSame('1', $request->getHeader('X-Trace'));
        self::assertSame('Bearer ak_test', $request->getHeader('Authorization'));
    }

    public function testHeaderNamesAreCaseInsensitive(): void
    {
        $transport = new MockTransport(Response::json(200, []));
        $client = $this->client($transport, ['headers' => ['x-app' => 'crm', 'authorization' => 'Bearer default']]);
        $client->send(['method' => 'GET', 'path' => '/a'], ['headers' => ['X-App' => 'shop', 'AUTHORIZATION' => 'Bearer other']]);
        $headers = $transport->getLastRequest()->getHeaders();

        $names = array_map('strtolower', array_keys($headers));
        self::assertSame(array_values(array_unique($names)), $names, 'имена заголовков не должны повторяться');
        self::assertSame(['Bearer ak_test'], array_values(array_filter($headers, static function (string $name): bool {
            return strtolower($name) === 'authorization';
        }, ARRAY_FILTER_USE_KEY)));
        self::assertSame('shop', $transport->getLastRequest()->getHeader('X-App'));
    }

    public function testApiKeyParameterIsMarkedSensitive(): void
    {
        if (PHP_VERSION_ID < 80000) {
            self::markTestSkipped('Атрибуты появились в PHP 8.0; в PHP 7.4 #[\SensitiveParameter] — комментарий.');
        }
        $parameter = (new \ReflectionMethod(BaseClient::class, '__construct'))->getParameters()[0];
        self::assertSame('apiKey', $parameter->getName());
        $names = array_map(static function ($attribute): string {
            return $attribute->getName();
        }, $parameter->getAttributes());
        self::assertSame(['SensitiveParameter'], $names);
    }

    /** @return iterable<string, array{int, class-string<AtloriumException>}> */
    public static function statusErrors(): iterable
    {
        yield '400' => [400, ValidationException::class];
        yield '401' => [401, AuthenticationException::class];
        yield '402' => [402, InsufficientCreditsException::class];
        yield '403' => [403, PermissionDeniedException::class];
        yield '404' => [404, NotFoundException::class];
        yield '500' => [500, ServerException::class];
        yield '501' => [501, ServerException::class];
        yield '418' => [418, AtloriumException::class];
    }

    /**
     * @dataProvider statusErrors
     *
     * @param class-string<AtloriumException> $class
     */
    public function testStatusErrorsAreTypedAndNotRetried(int $status, string $class): void
    {
        $body = ['error' => 'bad_request', 'title' => 'Заголовок', 'message' => 'Сообщение сервера', 'balance' => 1.5];
        $transport = new MockTransport(Response::json($status, $body, ['X-Request-Id' => 'req-1']));
        $client = $this->client($transport, ['maxRetries' => 2]);

        try {
            self::get($client);
            self::fail('Ожидалось исключение');
        } catch (AtloriumException $error) {
            self::assertSame($class, get_class($error));
            self::assertCount(1, $transport->getRequests());
            self::assertSame([], $this->sleeps);
            self::assertSame($status, $error->getStatusCode());
            self::assertSame('req-1', $error->getRequestId());
            self::assertSame('bad_request', $error->getErrorCode());
            self::assertSame('Заголовок', $error->getTitle());
            self::assertStringContainsString('Сообщение сервера', $error->getMessage());
            if ($error instanceof InsufficientCreditsException) {
                self::assertSame(1.5, $error->getBalance());
            }
        }
    }

    public function testPlainTextErrorBody(): void
    {
        $transport = new MockTransport(new Response(400, [], 'Hosts parameter is required.'));
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Hosts parameter is required.');
        self::get($this->client($transport));
    }

    public function testServiceUnavailableIsRetriedThenSucceeds(): void
    {
        $transport = new MockTransport(new Response(503, [], 'down'), new Response(503, [], 'down'), Response::json(200, [1]));
        $response = self::get($this->client($transport, ['maxRetries' => 2]));
        self::assertSame([1], $response->getData());
        self::assertCount(2, $this->sleeps);
        foreach ($this->sleeps as $delay) {
            self::assertGreaterThan(0.0, $delay);
            self::assertLessThanOrEqual(10.0, $delay);
        }
    }

    public function testServiceUnavailableMessageSaysMoneyIsNotCharged(): void
    {
        $transport = new MockTransport(new Response(503, [], ''), new Response(503, [], ''));
        $this->expectException(ServiceUnavailableException::class);
        $this->expectExceptionMessage('Деньги за такой ответ не списываются');
        self::get($this->client($transport, ['maxRetries' => 1]));
    }

    public function testServiceUnavailableIsServerException(): void
    {
        $transport = new MockTransport(new Response(503, [], 'down'));

        try {
            self::get($this->client($transport));
            self::fail('Ожидалось исключение');
        } catch (ServerException $error) {
            self::assertInstanceOf(ServiceUnavailableException::class, $error);
            self::assertSame(503, $error->getStatusCode());
            self::assertFalse($error->isMaintenance());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function maintenanceCodes(): iterable
    {
        yield 'service_disabled' => ['service_disabled'];
        yield 'maintenance' => ['maintenance'];
    }

    /** @dataProvider maintenanceCodes */
    public function testPlannedMaintenanceIsNotRetried(string $code): void
    {
        $body = ['error' => $code, 'title' => 'Плановые работы', 'message' => 'Сервис временно отключён'];
        $transport = new MockTransport(Response::json(503, $body), Response::json(200, ['ok' => true]));

        try {
            self::get($this->client($transport, ['maxRetries' => 2]));
            self::fail('Ожидалось исключение');
        } catch (ServiceUnavailableException $error) {
            self::assertTrue($error->isMaintenance());
            self::assertSame($code, $error->getErrorCode());
            self::assertCount(1, $transport->getRequests());
            self::assertSame([], $this->sleeps);
        }
    }

    public function testSiteWideMaintenanceWithLongRetryAfterThrowsImmediately(): void
    {
        $transport = new MockTransport(Response::json(503, ['error' => 'ServiceUnavailable'], ['Retry-After' => '3600']));

        try {
            self::get($this->client($transport, ['maxRetries' => 2]));
            self::fail('Ожидалось исключение');
        } catch (ServiceUnavailableException $error) {
            self::assertSame(3600.0, $error->getRetryAfter());
            self::assertCount(1, $transport->getRequests());
            self::assertSame([], $this->sleeps);
        }
    }

    public function testPermissionDeniedHasDefaultMessageAndIsNotRetried(): void
    {
        $transport = new MockTransport(Response::json(403, ['error' => 'service_not_in_account_terms']));

        try {
            self::get($this->client($transport, ['maxRetries' => 2]));
            self::fail('Ожидалось исключение');
        } catch (PermissionDeniedException $error) {
            self::assertSame('service_not_in_account_terms', $error->getErrorCode());
            self::assertStringContainsString('support@atlorium.com', $error->getMessage());
            self::assertCount(1, $transport->getRequests());
            self::assertSame([], $this->sleeps);
        }
    }

    public function testRetryAfterIsAvailableOnAnyError(): void
    {
        $transport = new MockTransport(new Response(500, ['Retry-After' => '7'], ''));

        try {
            self::get($this->client($transport));
            self::fail('Ожидалось исключение');
        } catch (ServerException $error) {
            self::assertSame(7.0, $error->getRetryAfter());
        }
    }

    public function testRateLimitRespectsRetryAfterHeader(): void
    {
        $transport = new MockTransport(new Response(429, ['Retry-After' => '3']), Response::json(200, ['a' => 1]));
        $response = self::get($this->client($transport, ['maxRetries' => 2]));
        self::assertSame(['a' => 1], $response->getData());
        self::assertSame([3.0], $this->sleeps);
    }

    public function testRateLimitTakesDelayFromBody(): void
    {
        $transport = new MockTransport(Response::json(429, ['error' => 'rate_limited_client', 'retryAfterSeconds' => 2]), Response::json(200, []));
        self::get($this->client($transport, ['maxRetries' => 2]));
        self::assertSame([2.0], $this->sleeps);
    }

    public function testTooLongRetryAfterThrowsImmediately(): void
    {
        $wait = (int) BaseClient::MAX_RETRY_DELAY + 1;
        $transport = new MockTransport(new Response(429, ['Retry-After' => (string) $wait]));

        try {
            self::get($this->client($transport, ['maxRetries' => 2]));
            self::fail('Ожидалось исключение');
        } catch (RateLimitException $error) {
            self::assertSame((float) $wait, $error->getRetryAfter());
            self::assertSame([], $this->sleeps);
        }
    }

    public function testRateLimitExhaustsRetries(): void
    {
        $transport = new MockTransport(new Response(429), new Response(429), new Response(429));
        try {
            self::get($this->client($transport, ['maxRetries' => 2]));
            self::fail('Ожидалось исключение');
        } catch (RateLimitException $error) {
            self::assertCount(3, $transport->getRequests());
            self::assertCount(2, $this->sleeps);
        }
    }

    public function testConnectionErrorsAreRetriedThenThrown(): void
    {
        $transport = new MockTransport(new ConnectionException('boom'), new ConnectionException('boom'), new ConnectionException('boom'));
        try {
            self::get($this->client($transport, ['maxRetries' => 2]));
            self::fail('Ожидалось исключение');
        } catch (ConnectionException $error) {
            self::assertCount(3, $transport->getRequests());
            self::assertCount(2, $this->sleeps);
            self::assertNull($error->getStatusCode());
        }
    }

    private static function post(Client $client): ApiResponse
    {
        return $client->send(['method' => 'POST', 'path' => '/api/test', 'body' => []]);
    }

    public function testPostIsNotRetriedWhenRequestMayHaveReachedServer(): void
    {
        foreach ([new TimeoutException('slow'), new ConnectionException('reset')] as $failure) {
            $transport = new MockTransport($failure, Response::json(200, ['ok' => true]));
            try {
                self::post($this->client($transport, ['maxRetries' => 2]));
                self::fail('Ожидалось исключение');
            } catch (ConnectionException $error) {
                self::assertSame($failure, $error);
                self::assertCount(1, $transport->getRequests());
                self::assertSame([], $this->sleeps);
            }
        }
    }

    public function testPostIsRetriedWhenRequestWasNotSent(): void
    {
        $failure = ConnectionException::requestNotSent('dns');
        self::assertTrue($failure->isRequestNotSent());
        self::assertFalse((new ConnectionException('reset'))->isRequestNotSent());
        $transport = new MockTransport($failure, Response::json(200, ['ok' => true]));
        $response = self::post($this->client($transport, ['maxRetries' => 2]));
        self::assertSame(['ok' => true], $response->getData());
        self::assertCount(1, $this->sleeps);
    }

    public function testPostIsRetriedAfterConnectTimeout(): void
    {
        $failure = TimeoutException::connectTimeout('connect');
        self::assertTrue($failure->isRequestNotSent());
        self::assertFalse((new TimeoutException('read'))->isRequestNotSent());
        $transport = new MockTransport($failure, Response::json(200, ['ok' => true]));
        $response = self::post($this->client($transport, ['maxRetries' => 2]));
        self::assertSame(['ok' => true], $response->getData());
        self::assertCount(2, $transport->getRequests());
    }

    public function testPostIsRetriedOnServiceUnavailable(): void
    {
        $transport = new MockTransport(new Response(503, [], 'down'), Response::json(200, ['ok' => true]));
        $response = self::post($this->client($transport, ['maxRetries' => 2]));
        self::assertSame(['ok' => true], $response->getData());
        self::assertCount(2, $transport->getRequests());
    }

    public function testMaxRetryDelay(): void
    {
        // PHPUnit работает в CLI — там действует длинный потолок.
        self::assertSame(BaseClient::MAX_RETRY_DELAY, $this->client(new MockTransport())->getMaxRetryDelay());

        $transport = new MockTransport(new Response(429, ['Retry-After' => '5']), Response::json(200, []));
        $client = $this->client($transport, ['maxRetries' => 2, 'maxRetryDelay' => 3]);
        self::assertSame(3.0, $client->getMaxRetryDelay());
        try {
            self::get($client);
            self::fail('Ожидалось исключение');
        } catch (RateLimitException $error) {
            self::assertSame(5.0, $error->getRetryAfter());
            self::assertSame([], $this->sleeps);
        }

        self::assertSame(10.0, $client->withOptions(['maxRetryDelay' => 10])->getMaxRetryDelay());
    }

    public function testTimeoutIsConnectionException(): void
    {
        $transport = new MockTransport(new TimeoutException('slow'), Response::json(200, ['ok' => true]));
        $response = self::get($this->client($transport, ['maxRetries' => 1]));
        self::assertSame(['ok' => true], $response->getData());
        self::assertTrue(is_subclass_of(TimeoutException::class, ConnectionException::class));
    }

    public function testEffectiveTimeout(): void
    {
        $transport = new MockTransport();
        $transport->setDefault(static function (Request $request): Response {
            return Response::json(200, []);
        });
        $client = $this->client($transport, ['timeout' => 10]);
        $client->send(['method' => 'GET', 'path' => '/a']);
        $client->send(['method' => 'GET', 'path' => '/a', 'defaultTimeout' => 600.0]);
        $client->send(['method' => 'GET', 'path' => '/a', 'defaultTimeout' => 600.0], ['timeout' => 5]);
        $timeouts = array_map(static function (Request $request): float {
            return $request->getTimeout();
        }, $transport->getRequests());
        self::assertSame([10.0, 600.0, 5.0], $timeouts);
    }

    public function testApiResponseMetadata(): void
    {
        $headers = ['X-Atlorium-Sandbox' => 'true', 'X-RateLimit-Remaining' => '7', 'X-RateLimit-Limit' => '10'];
        $response = self::get($this->client(new MockTransport(Response::json(200, ['found' => true], $headers))));
        self::assertSame(['found' => true], $response->getData());
        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->isSandbox());
        self::assertSame(['limit' => 10, 'remaining' => 7, 'reset' => null], $response->getRateLimit());
        self::assertNull($response->getRequestId());
        self::assertNull($response->getRetryAfter());
    }

    public function testResponseTypes(): void
    {
        $transport = new MockTransport();
        $transport->setDefault(static function (Request $request): Response {
            return new Response(200, ['Content-Type' => 'text/csv'], '%PDF');
        });
        $client = $this->client($transport);
        self::assertSame('%PDF', $client->send(['method' => 'GET', 'path' => '/a', 'responseType' => 'bytes'])->getData());
        self::assertSame('%PDF', $client->send(['method' => 'GET', 'path' => '/a', 'responseType' => 'auto'])->getData());
        self::assertSame('%PDF', $client->send(['method' => 'GET', 'path' => '/a', 'responseType' => 'text'])->getData());
    }

    public function testEmptyBodyIsNull(): void
    {
        self::assertNull(self::get($this->client(new MockTransport(new Response(200))))->getData());
    }

    public function testEmptyBodyOfTextOrJsonResponseIsEmptyString(): void
    {
        $client = $this->client(new MockTransport(new Response(200), new Response(204, ['Content-Type' => 'application/json'])));
        self::assertSame('', $client->send(['method' => 'GET', 'path' => '/a', 'responseType' => 'auto'])->getData());
        self::assertSame('', $client->send(['method' => 'DELETE', 'path' => '/a', 'responseType' => 'auto'])->getData());
    }

    public function testInvalidJsonThrows(): void
    {
        $transport = new MockTransport(new Response(200, ['Content-Type' => 'application/json'], '{oops'));
        $this->expectException(AtloriumException::class);
        self::get($this->client($transport));
    }

    public function testWithOptionsKeepsKeyAndTransport(): void
    {
        $transport = new MockTransport(Response::json(200, []));
        $client = $this->client($transport);
        $copy = $client->withOptions(['timeout' => 99, 'maxRetries' => 5]);
        self::assertSame(99.0, $copy->getTimeout());
        self::assertSame(5, $copy->getMaxRetries());
        self::assertSame(0, $client->getMaxRetries());
        self::assertSame($client->getTransport(), $copy->getTransport());
        self::assertNotSame($client->egrul, $copy->egrul);
        self::get($copy);
        self::assertSame('Bearer ak_test', $transport->getLastRequest()->getHeader('Authorization'));
    }

    public function testClientMagicProperties(): void
    {
        $client = $this->client(new MockTransport());
        self::assertTrue(isset($client->egrul));
        self::assertFalse(isset($client->nothing));
        self::assertSame($client->egrul, $client->egrul);

        $this->expectException(\OutOfRangeException::class);
        $client->__get('nothing');
    }

    public function testClientPropertiesAreReadOnly(): void
    {
        $client = $this->client(new MockTransport());
        $this->expectException(\LogicException::class);
        $client->__set('egrul', null);
    }

    public function testMockTransportWithEmptyQueue(): void
    {
        $this->expectException(\LogicException::class);
        self::get($this->client(new MockTransport()));
    }

    public function testMockTransportAcceptsCallables(): void
    {
        $transport = new MockTransport(static function (Request $request): Response {
            return Response::json(200, ['path' => $request->getPath()]);
        });
        self::assertSame(['path' => '/api/test'], self::get($this->client($transport))->getData());
    }

    public function testMockTransportRejectsUnknownItems(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MockTransport('not a response');
    }

    public function testMockTransportHandlerMustReturnResponse(): void
    {
        $transport = new MockTransport(static function (): string {
            return 'oops';
        });
        $this->expectException(\LogicException::class);
        self::get($this->client($transport));
    }

    public function testInvalidSleeperIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('ak_test', ['transport' => new MockTransport(), 'sleeper' => 'no-such-function']);
    }

    public function testDebugInfoDoesNotContainKey(): void
    {
        $client = $this->client(new MockTransport());
        $info = $client->__debugInfo();
        self::assertSame(['baseUrl', 'isSandbox', 'timeout', 'maxRetries', 'maxRetryDelay', 'transport'], array_keys($info));
        self::assertSame(MockTransport::class, $info['transport']);
        self::assertStringNotContainsString('ak_test', print_r($client, true));
        self::assertStringNotContainsString('ak_test', var_export($client->__debugInfo(), true));
    }

    public function testVersionMatchesChangelog(): void
    {
        $changelog = (string) file_get_contents(__DIR__ . '/../CHANGELOG.md');
        self::assertStringContainsString('## [' . BaseClient::VERSION . ']', $changelog);
    }
}
