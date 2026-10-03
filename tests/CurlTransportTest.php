<?php

declare(strict_types=1);

namespace Atlorium\Tests;

use Atlorium\BaseClient;
use Atlorium\Client;
use Atlorium\Exception\ConnectionException;
use Atlorium\Exception\NotFoundException;
use Atlorium\Exception\TimeoutException;
use Atlorium\Http\CurlTransport;
use Atlorium\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * CurlTransport против настоящего HTTP-сервера — встроенного сервера PHP на 127.0.0.1
 * (маршруты — tests/server/router.php). Во внешнюю сеть тесты не ходят. Если сервер запустить
 * не удалось, тесты, которым он нужен, пропускаются.
 */
final class CurlTransportTest extends TestCase
{
    /** @var resource|null */
    private static $server;

    /** @var array<int, resource> */
    private static array $pipes = [];

    private static ?string $baseUrl = null;

    private static string $skipReason = 'встроенный сервер PHP не запускался';

    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('curl')) {
            self::$skipReason = 'нет расширения curl';

            return;
        }
        if (!function_exists('proc_open')) {
            self::$skipReason = 'функция proc_open() недоступна';

            return;
        }
        $port = self::freePort();
        if ($port === null) {
            self::$skipReason = 'не удалось подобрать свободный порт';

            return;
        }

        $log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'atlorium-php-test-server.log';
        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/server/router.php'],
            [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']],
            $pipes
        );
        if (!is_resource($process)) {
            self::$skipReason = 'не удалось запустить встроенный сервер PHP';

            return;
        }
        self::$server = $process;
        self::$pipes = $pipes;

        $deadline = microtime(true) + 10.0;
        while (microtime(true) < $deadline) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if (is_resource($socket)) {
                fclose($socket);
                self::$baseUrl = 'http://127.0.0.1:' . $port;

                return;
            }
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            usleep(50000);
        }

        self::$skipReason = 'встроенный сервер PHP не ответил (журнал: ' . $log . ')';
        self::stopServer();
    }

    public static function tearDownAfterClass(): void
    {
        self::stopServer();
    }

    public function testGetWithQueryAndHeaders(): void
    {
        $raw = self::client()->send([
            'method' => 'GET',
            'path' => '/echo',
            'query' => ['a' => 1, 'text' => 'при вет', 'flag' => true],
        ]);
        self::assertSame(200, $raw->getStatusCode());
        self::assertSame('yes', $raw->getHeader('X-Test'));

        $data = $raw->getData();
        self::assertIsArray($data);
        self::assertSame('GET', $data['method']);
        self::assertSame('/echo', $data['path']);
        self::assertSame(['a' => '1', 'text' => 'при вет', 'flag' => 'true'], $data['query']);
        self::assertSame('Bearer ak_test', $data['headers']['authorization']);
        self::assertSame('application/json', $data['headers']['accept']);
        self::assertStringStartsWith('atlorium-php/' . BaseClient::VERSION . ' (php/', $data['headers']['user-agent']);
        self::assertSame('', $data['body']);
    }

    public function testPostJsonBody(): void
    {
        $data = self::client()->send([
            'method' => 'POST',
            'path' => '/echo',
            'body' => ['message' => 'Привет', 'n' => 1.5],
        ])->getData();
        self::assertIsArray($data);
        self::assertSame('POST', $data['method']);
        self::assertSame('application/json', $data['headers']['content-type']);
        self::assertSame('Bearer ak_test', $data['headers']['authorization']);
        self::assertSame('{"message":"Привет","n":1.5}', $data['body']);
    }

    public function testBinaryResponseIsReturnedAsIs(): void
    {
        $raw = self::client()->send(['method' => 'GET', 'path' => '/binary', 'responseType' => 'bytes']);
        self::assertSame("%PDF-1.7\n\x00\x01\xFF\xFE", $raw->getData());
        self::assertSame('application/pdf', $raw->getHeader('Content-Type'));
    }

    public function testErrorStatusBecomesTypedException(): void
    {
        try {
            self::client()->send(['method' => 'GET', 'path' => '/error']);
            self::fail('Ожидалось исключение');
        } catch (NotFoundException $error) {
            self::assertSame(404, $error->getStatusCode());
            self::assertSame('not_found', $error->getErrorCode());
            self::assertSame('Не найдено', $error->getTitle());
            self::assertStringContainsString('Нет такой организации', $error->getMessage());
            self::assertSame('yes', $error->getHeader('X-Test'));
        }
    }

    public function testReadTimeoutIsNotRetriedForPost(): void
    {
        $client = self::client();
        $started = microtime(true);

        try {
            $client->send(['method' => 'POST', 'path' => '/sleep', 'body' => []], ['timeout' => 0.5, 'maxRetries' => 2]);
            self::fail('Ожидалось исключение');
        } catch (TimeoutException $error) {
            // Запрос ушёл на сервер, ответа не дождались: повторять POST нельзя.
            self::assertFalse($error->isRequestNotSent());
            self::assertNull($error->getStatusCode());
            self::assertStringContainsString('0.5 с', $error->getMessage());
            self::assertLessThan(1.9, microtime(true) - $started);
        }
    }

    public function testConnectionRefusedMeansRequestNotSent(): void
    {
        $port = self::freePort();
        if ($port === null) {
            self::markTestSkipped('не удалось подобрать свободный порт');
        }

        try {
            (new CurlTransport())->send(new Request('POST', 'http://127.0.0.1:' . $port . '/x', [], '{}', 5.0));
            self::fail('Ожидалось исключение');
        } catch (ConnectionException $error) {
            self::assertNotInstanceOf(TimeoutException::class, $error);
            self::assertTrue($error->isRequestNotSent());
            self::assertNull($error->getStatusCode());
            self::assertStringContainsString('(cURL ' . CURLE_COULDNT_CONNECT . ')', $error->getMessage());
        }
    }

    public function testPostIsRetriedWhenConnectionWasRefused(): void
    {
        $port = self::freePort();
        if ($port === null) {
            self::markTestSkipped('не удалось подобрать свободный порт');
        }
        $sleeps = [];
        $client = new Client('ak_test', [
            'baseUrl' => 'http://127.0.0.1:' . $port,
            'transport' => new CurlTransport(),
            'maxRetries' => 2,
            'sleeper' => static function (float $seconds) use (&$sleeps): void {
                $sleeps[] = $seconds;
            },
        ]);

        try {
            $client->send(['method' => 'POST', 'path' => '/x', 'body' => []]);
            self::fail('Ожидалось исключение');
        } catch (ConnectionException $error) {
            self::assertTrue($error->isRequestNotSent());
            self::assertCount(2, $sleeps);
        }
    }

    /** @return iterable<string, array{int, float, float, int, int, bool}> */
    public static function sentStates(): iterable
    {
        yield 'нет ошибки' => [0, 0.01, 0.01, 100, 200, false];
        yield 'отказ в соединении' => [CURLE_COULDNT_CONNECT, 0.0, 0.0, 0, 0, true];
        yield 'DNS' => [CURLE_COULDNT_RESOLVE_HOST, 0.0, 0.0, 0, 0, true];
        yield 'таймаут подключения' => [CURLE_OPERATION_TIMEDOUT, 0.0, 0.0, 0, 0, true];
        yield 'таймаут чтения' => [CURLE_OPERATION_TIMEDOUT, 0.001, 0.002, 150, 0, false];
        yield 'таймаут после соединения без отправки' => [CURLE_OPERATION_TIMEDOUT, 0.001, 0.0, 0, 0, false];
        yield 'обрыв после ответа сервера' => [56, 0.0, 0.0, 0, 200, false];
        yield 'обрыв при чтении' => [56, 0.001, 0.002, 150, 0, false];
    }

    /** @dataProvider sentStates */
    public function testRequestNotSentClassification(
        int $errno,
        float $connectTime,
        float $preTransferTime,
        int $requestSize,
        int $status,
        bool $expected
    ): void {
        self::assertSame($expected, CurlTransport::isRequestNotSent($errno, $connectTime, $preTransferTime, $requestSize, $status));
    }

    private static function client(): Client
    {
        if (self::$baseUrl === null) {
            self::markTestSkipped(self::$skipReason);
        }

        return new Client('ak_test', [
            'baseUrl' => self::$baseUrl,
            'transport' => new CurlTransport(),
            'maxRetries' => 0,
            'timeout' => 10,
        ]);
    }

    private static function freePort(): ?int
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            return null;
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        if (!is_string($name)) {
            return null;
        }
        $colon = strrpos($name, ':');
        if ($colon === false) {
            return null;
        }
        $port = (int) substr($name, $colon + 1);

        return $port > 0 ? $port : null;
    }

    private static function stopServer(): void
    {
        foreach (self::$pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        self::$pipes = [];
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        self::$server = null;
        self::$baseUrl = null;
    }
}
