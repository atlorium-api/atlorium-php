<?php

declare(strict_types=1);

namespace Atlorium\Tests;

use Atlorium\Client;
use Atlorium\Enum\CronTemplateType;
use Atlorium\Enum\DayOfWeek;
use Atlorium\Exception\NotFoundException;
use Atlorium\Http\MockTransport;
use Atlorium\Http\Request;
use Atlorium\Http\Response;
use Atlorium\ImageInput;
use PHPUnit\Framework\TestCase;

/**
 * Тесты ресурсов на MockTransport: каждый из 59 методов (обычный вызов и withRawResponse()),
 * плюс особые случаи — PDF, CSV, изображения, таймауты операций.
 *
 * Таблица cases() написана руками, независимо от генератора: она проверяет, что сгенерированный
 * код шлёт ровно тот запрос, который ждёт сервер.
 */
final class ResourcesTest extends TestCase
{
    private const NO_BODY = '__no_body__';

    /** Тело ответа okTransport(). */
    private const OK_BODY = '{"ok":true}';

    /**
     * Методы, которые возвращают строку, а не разобранный JSON: PDF-выписка — байты как есть,
     * удаление сессии ИИ-чата — текст (JSON-объект без поля message приводится к JSON-строке).
     */
    private const STRING_RESULTS = ['egrul.getExcerpt', 'aichat.deleteSession'];

    /** Методы, у которых и withRawResponse()->getData() — исходные байты. */
    private const BYTES_RESULTS = ['egrul.getExcerpt'];

    /**
     * @return array<string, array{string, string, list<mixed>, string, string, string, array<string, string>, mixed}>
     *     [ресурс, метод, аргументы, HTTP, маршрут спеки, путь, query, тело]
     */
    public static function cases(): array
    {
        $png = "\x89PNG";
        $cases = [
            // ЕГРЮЛ
            ['egrul', 'get', ['7707083893', ['extended' => true]], 'GET', '/api/egrul/{query}', '/api/egrul/7707083893', ['extended' => 'true']],
            ['egrul', 'search', ['Сбербанк', ['limit' => 5]], 'GET', '/api/egrul/search', '/api/egrul/search', ['query' => 'Сбербанк', 'limit' => '5']],
            ['egrul', 'getExcerpt', ['7707083893'], 'GET', '/api/egrul/{query}/excerpt', '/api/egrul/7707083893/excerpt'],
            // БИК
            ['cbr', 'get', ['044525225'], 'GET', '/api/cbr/{bik}', '/api/cbr/044525225'],
            ['cbr', 'search', ['Сбер', ['limit' => 3]], 'GET', '/api/cbr/search', '/api/cbr/search', ['query' => 'Сбер', 'limit' => '3']],
            ['cbr', 'getStats', [], 'GET', '/api/cbr/stats', '/api/cbr/stats'],
            // SWIFT
            ['swift', 'get', ['SABRRUMM'], 'GET', '/api/swift/{bic}', '/api/swift/SABRRUMM'],
            ['swift', 'search', ['Sberbank'], 'GET', '/api/swift/search', '/api/swift/search', ['query' => 'Sberbank']],
            ['swift', 'validate', ['SABRRUMMXXX'], 'GET', '/api/swift/validate/{bic}', '/api/swift/validate/SABRRUMMXXX'],
            ['swift', 'getStats', [], 'GET', '/api/swift/stats', '/api/swift/stats'],
            // BIN
            ['bin', 'lookup', ['424242', ['customerIp' => '8.8.8.8']], 'GET', '/api/bin/{bin}', '/api/bin/424242', ['customerIp' => '8.8.8.8']],
            ['bin', 'validate', ['424242'], 'GET', '/api/bin/validate/{bin}', '/api/bin/validate/424242'],
            // AML
            ['aml', 'screen', ['TXyz', 'TRX'], 'POST', '/api/aml/screen', '/api/aml/screen', [], ['walletAddress' => 'TXyz', 'blockchain' => 'TRX']],
            ['aml', 'validate', ['TXyz', 'TRX'], 'GET', '/api/aml/validate', '/api/aml/validate', ['walletAddress' => 'TXyz', 'blockchain' => 'TRX']],
            // IP
            ['ipinfo', 'lookup', ['8.8.8.8', ['extended' => false]], 'GET', '/api/ipinfo/{ip}', '/api/ipinfo/8.8.8.8', ['extended' => 'false']],
            // ГАР
            ['gar', 'search', ['Москва Тверская', ['limit' => 2]], 'GET', '/api/gar/search', '/api/gar/search', ['query' => 'Москва Тверская', 'limit' => '2']],
            ['gar', 'suggest', ['Твер'], 'GET', '/api/gar/suggest', '/api/gar/suggest', ['query' => 'Твер']],
            ['gar', 'getObject', ['0c5b2444-70a0-4932-980c-b4dc0d3f02b5'], 'GET', '/api/gar/object/{guid}', '/api/gar/object/0c5b2444-70a0-4932-980c-b4dc0d3f02b5'],
            ['gar', 'getObjectById', [1234567], 'GET', '/api/gar/object/id/{objectId}', '/api/gar/object/id/1234567'],
            ['gar', 'getChildren', [1405113, ['limit' => 10]], 'GET', '/api/gar/children/{parentObjectId}', '/api/gar/children/1405113', ['limit' => '10']],
            ['gar', 'getHierarchy', ['1405113'], 'GET', '/api/gar/hierarchy/{objectId}', '/api/gar/hierarchy/1405113'],
            ['gar', 'getStats', [], 'GET', '/api/gar/stats', '/api/gar/stats'],
            ['gar', 'listRegions', [], 'GET', '/api/gar/regions', '/api/gar/regions'],
            ['gar', 'getRegionStats', ['77'], 'GET', '/api/gar/region/{regionCode}/stats', '/api/gar/region/77/stats'],
            // Адрес, телефон, почта
            ['addressstd', 'standardize', ['москва тверская 1', ['region' => 77, 'bind' => true, 'geocode' => false]], 'GET', '/api/addressstd', '/api/addressstd', ['address' => 'москва тверская 1', 'region' => '77', 'bind' => 'true', 'geocode' => 'false']],
            ['phone', 'validate', ['+79161234567', ['countryCode' => 'RU']], 'GET', '/api/phone/{number}', '/api/phone/+79161234567', ['countryCode' => 'RU']],
            ['email', 'validate', ['test@example.com'], 'GET', '/api/emailvalidation', '/api/emailvalidation', ['email' => 'test@example.com']],
            // OCR
            ['ocr', 'recognize', [$png, ['mode' => 'auto']], 'POST', '/api/ocr/image-to-text', '/api/ocr/image-to-text', [], ['image' => base64_encode($png), 'mode' => 'auto']],
            // DNS
            ['dns', 'lookup', ['atlorium.com', ['resolver' => '1.1.1.1']], 'GET', '/api/dns/lookup', '/api/dns/lookup', ['domain' => 'atlorium.com', 'resolver' => '1.1.1.1']],
            ['dns', 'checkPropagation', ['atlorium.com', ['resolvers' => ['1.1.1.1', '8.8.8.8']]], 'GET', '/api/dns/propagation', '/api/dns/propagation', ['domain' => 'atlorium.com', 'resolvers' => '1.1.1.1,8.8.8.8']],
            // CIDR
            ['cidr', 'calculate', ['192.168.1.10', 24], 'GET', '/api/cidr/calculate', '/api/cidr/calculate', ['ip' => '192.168.1.10', 'mask' => '24']],
            ['cidr', 'split', ['10.0.0.0/24', 26], 'GET', '/api/cidr/subnets', '/api/cidr/subnets', ['cidr' => '10.0.0.0/24', 'newMask' => '26']],
            ['cidr', 'check', ['10.0.0.0/8', '10.1.2.3'], 'GET', '/api/cidr/check', '/api/cidr/check', ['cidr' => '10.0.0.0/8', 'ip' => '10.1.2.3']],
            ['cidr', 'supernet', [['10.0.0.0/24', '10.0.1.0/24']], 'POST', '/api/cidr/supernet', '/api/cidr/supernet', [], ['inputs' => ['10.0.0.0/24', '10.0.1.0/24']]],
            // Cron
            ['cron', 'evaluate', ['*/15 * * * *', ['timeZoneId' => 'Europe/Moscow', 'take' => 3]], 'POST', '/api/cron/evaluate', '/api/cron/evaluate', [], ['expression' => '*/15 * * * *', 'timeZoneId' => 'Europe/Moscow', 'take' => 3]],
            ['cron', 'build', [['template' => CronTemplateType::WEEKLY, 'timeOfDay' => '09:30:00', 'weekDays' => [DayOfWeek::MONDAY, DayOfWeek::FRIDAY]]], 'POST', '/api/cron/build', '/api/cron/build', [], ['template' => 5, 'timeOfDay' => '09:30:00', 'weekDays' => [1, 5]]],
            // SSL
            ['certificate', 'check', ['atlorium.com', ['port' => 443]], 'GET', '/api/certificate', '/api/certificate', ['host' => 'atlorium.com', 'port' => '443']],
            ['certificate', 'checkUrl', ['https://atlorium.com'], 'GET', '/api/certificate/url', '/api/certificate/url', ['url' => 'https://atlorium.com']],
            ['certificate', 'checkBatch', [['a.ru', 'b.ru']], 'GET', '/api/certificate/batch', '/api/certificate/batch', ['hosts' => 'a.ru,b.ru']],
            // Погода
            ['weather', 'get', [55.75, 37.62], 'GET', '/api/weather', '/api/weather', ['latitude' => '55.75', 'longitude' => '37.62']],
            // ИИ-чат
            ['aichat', 'send', ['Привет', ['sessionId' => 'abc']], 'POST', '/api/aichat/send', '/api/aichat/send', [], ['message' => 'Привет', 'sessionId' => 'abc']],
            ['aichat', 'getSession', ['abc'], 'GET', '/api/aichat/session/{sessionId}', '/api/aichat/session/abc'],
            ['aichat', 'deleteSession', ['abc'], 'DELETE', '/api/aichat/session/{sessionId}', '/api/aichat/session/abc'],
            ['aichat', 'listModels', [], 'GET', '/api/aichat/models', '/api/aichat/models'],
            // Прямой геокодинг
            ['geocodeforward', 'search', ['Москва, Тверская 1', ['size' => 1, 'exactOnly' => true]], 'GET', '/api/geocodeforward', '/api/geocodeforward', ['text' => 'Москва, Тверская 1', 'size' => '1', 'exactOnly' => 'true']],
            ['geocodeforward', 'searchStructured', [['address' => 'Тверская 1', 'locality' => 'Москва', 'postalCode' => '125009']], 'GET', '/api/geocodeforward/structured', '/api/geocodeforward/structured', ['address' => 'Тверская 1', 'locality' => 'Москва', 'postalCode' => '125009']],
            ['geocodeforward', 'searchBatch', [['Москва', 'Казань'], ['sizePerQuery' => 2]], 'POST', '/api/geocodeforward/batch', '/api/geocodeforward/batch', [], ['queries' => ['Москва', 'Казань'], 'sizePerQuery' => 2]],
            ['geocodeforward', 'getPlace', ['osm:123', ['includeBoundary' => true]], 'GET', '/api/geocodeforward/place', '/api/geocodeforward/place', ['placeId' => 'osm:123', 'includeBoundary' => 'true']],
            ['geocodeforward', 'searchPostcode', ['125009'], 'GET', '/api/geocodeforward/postcode', '/api/geocodeforward/postcode', ['code' => '125009']],
            // Обратный геокодинг
            ['geocodereverse', 'lookup', [55.75, 37.62, ['includeAdmin' => true]], 'GET', '/api/geocodereverse', '/api/geocodereverse', ['lat' => '55.75', 'lon' => '37.62', 'includeAdmin' => 'true']],
            ['geocodereverse', 'nearby', [55.75, 37.62, ['radiusKm' => 1.5, 'size' => 5]], 'GET', '/api/geocodereverse/nearby', '/api/geocodereverse/nearby', ['lat' => '55.75', 'lon' => '37.62', 'radiusKm' => '1.5', 'size' => '5']],
            // Тестовые данные
            ['testdata', 'generate', [['count' => 2, 'fields' => ['fullName', 'innPerson'], 'seed' => 42]], 'GET', '/api/testdata', '/api/testdata', ['count' => '2', 'fields' => 'fullName,innPerson', 'seed' => '42']],
            ['testdata', 'generateWithBody', [['count' => 2, 'fields' => ['fullName'], 'gender' => 'female']], 'POST', '/api/testdata', '/api/testdata', [], ['count' => 2, 'fields' => ['fullName'], 'gender' => 'female']],
            ['testdata', 'listFields', [], 'GET', '/api/testdata/fields', '/api/testdata/fields'],
            // Проверка ссылок
            ['urlcheck', 'check', ['https://example.com'], 'GET', '/api/urlcheck', '/api/urlcheck', ['url' => 'https://example.com']],
            ['urlcheck', 'checkBatch', [['https://a.ru', 'https://b.ru']], 'POST', '/api/urlcheck/batch', '/api/urlcheck/batch', [], ['https://a.ru', 'https://b.ru']],
            // Аудит сайта
            ['pagespeed', 'audit', ['https://atlorium.com', ['strategy' => 'mobile', 'forceRefresh' => true]], 'GET', '/api/pagespeed', '/api/pagespeed', ['url' => 'https://atlorium.com', 'strategy' => 'mobile', 'forceRefresh' => 'true']],
            ['pagespeed', 'auditBatch', [['https://a.ru'], ['strategy' => 'desktop']], 'POST', '/api/pagespeed/batch', '/api/pagespeed/batch', ['strategy' => 'desktop'], ['https://a.ru']],
            // Модерация изображений
            ['imagecheck', 'analyze', ['img', ['includeWebSearch' => true]], 'POST', '/api/imagecheck', '/api/imagecheck', [], ['image' => base64_encode('img'), 'includeWebSearch' => true]],
        ];

        $result = [];
        foreach ($cases as $case) {
            $result[$case[0] . '.' . $case[1]] = [$case[0], $case[1], $case[2], $case[3], $case[4], $case[5], $case[6] ?? [], array_key_exists(7, $case) ? $case[7] : self::NO_BODY];
        }

        return $result;
    }

    /** @param array<string, mixed> $options */
    private static function client(MockTransport $transport, array $options = []): Client
    {
        return new Client('ak_test', $options + ['transport' => $transport, 'maxRetries' => 0]);
    }

    private static function okTransport(): MockTransport
    {
        $transport = new MockTransport();
        $transport->setDefault(static function (Request $request): Response {
            return Response::json(200, ['ok' => true], ['X-Atlorium-Sandbox' => 'true']);
        });

        return $transport;
    }

    /** @return array<string, mixed> */
    private static function fixture(string $name): array
    {
        $data = json_decode((string) file_get_contents(__DIR__ . '/fixtures/' . $name . '.json'), true);
        self::assertIsArray($data);

        return $data;
    }

    /**
     * @param array<string, string> $query
     * @param mixed                 $body
     */
    private static function assertRequest(?Request $request, string $http, string $path, array $query, $body): void
    {
        self::assertNotNull($request);
        self::assertSame($http, $request->getMethod());
        self::assertSame($path, rawurldecode($request->getPath()));
        self::assertSame($query, $request->getQuery());
        self::assertSame('Bearer ak_test', $request->getHeader('Authorization'));
        if ($body === self::NO_BODY) {
            self::assertNull($request->getBody());
        } else {
            self::assertSame('application/json', $request->getHeader('Content-Type'));
            self::assertSame($body, $request->getJson());
        }
    }

    public function testEveryOperationInSpecsHasACase(): void
    {
        $routes = [];
        foreach (glob(__DIR__ . '/../spec/*_ru.json') ?: [] as $file) {
            $spec = json_decode((string) file_get_contents($file), true);
            self::assertIsArray($spec);
            foreach ($spec['paths'] as $path => $methods) {
                foreach (array_keys($methods) as $method) {
                    if (in_array($method, ['get', 'post', 'put', 'delete', 'patch'], true)) {
                        $routes[] = strtoupper($method) . ' ' . strtolower($path);
                    }
                }
            }
        }
        $tested = array_map(static function (array $case): string {
            return $case[3] . ' ' . strtolower($case[4]);
        }, array_values(self::cases()));

        sort($routes);
        sort($tested);
        self::assertCount(59, $tested);
        self::assertSame($routes, $tested);
    }

    /**
     * @dataProvider cases
     *
     * @param list<mixed>           $arguments
     * @param array<string, string> $query
     * @param mixed                 $body
     */
    public function testRequestShape(string $resource, string $method, array $arguments, string $http, string $route, string $path, array $query, $body): void
    {
        $transport = self::okTransport();
        $client = self::client($transport);
        $result = $client->{$resource}->{$method}(...$arguments);
        self::assertCount(1, $transport->getRequests());
        self::assertRequest($transport->getLastRequest(), $http, $path, $query, $body);
        $expected = in_array($resource . '.' . $method, self::STRING_RESULTS, true) ? self::OK_BODY : ['ok' => true];
        self::assertSame($expected, $result);
    }

    /**
     * @dataProvider cases
     *
     * @param list<mixed>           $arguments
     * @param array<string, string> $query
     * @param mixed                 $body
     */
    public function testRawResponseShape(string $resource, string $method, array $arguments, string $http, string $route, string $path, array $query, $body): void
    {
        $transport = self::okTransport();
        $client = self::client($transport);
        $raw = $client->{$resource}->withRawResponse()->{$method}(...$arguments);
        self::assertRequest($transport->getLastRequest(), $http, $path, $query, $body);
        self::assertSame(200, $raw->getStatusCode());
        self::assertTrue($raw->isSandbox());
        $expected = in_array($resource . '.' . $method, self::BYTES_RESULTS, true) ? self::OK_BODY : ['ok' => true];
        self::assertSame($expected, $raw->getData());
    }

    public function testOkTransportBody(): void
    {
        $response = self::okTransport()->send(new Request('GET', 'https://atlorium.test/'));
        self::assertSame(self::OK_BODY, $response->getBody());
    }

    public function testPathSegmentsAreEscaped(): void
    {
        $transport = self::okTransport();
        $client = self::client($transport);
        $client->phone->validate('+7 916 123-45-67');
        $client->egrul->get('ООО/Ромашка');
        $requests = $transport->getRequests();
        self::assertSame('/api/phone/%2B7%20916%20123-45-67', $requests[0]->getPath());
        self::assertStringStartsWith('/api/egrul/%D0%9E%D0%9E%D0%9E%2F', $requests[1]->getPath());
    }

    public function testSingleStringForListArgumentsIsNotSplit(): void
    {
        $transport = self::okTransport();
        $client = self::client($transport);
        $client->urlcheck->checkBatch('https://a.ru');
        $client->certificate->checkBatch('a.ru');
        $client->cidr->supernet('10.0.0.0/24');
        $client->testdata->generateWithBody(['fields' => 'fullName']);
        $requests = $transport->getRequests();
        self::assertSame(['https://a.ru'], $requests[0]->getJson());
        self::assertSame(['hosts' => 'a.ru'], $requests[1]->getQuery());
        self::assertSame(['inputs' => ['10.0.0.0/24']], $requests[2]->getJson());
        self::assertSame(['fields' => ['fullName']], $requests[3]->getJson());
    }

    public function testOptionalParametersAreNotSent(): void
    {
        $transport = self::okTransport();
        $client = self::client($transport);
        $client->geocodereverse->lookup(55.75, 37.62);
        $client->aichat->send('Привет');
        $client->cron->build();
        $requests = $transport->getRequests();
        self::assertSame(['lat' => '55.75', 'lon' => '37.62'], $requests[0]->getQuery());
        self::assertSame(['message' => 'Привет'], $requests[1]->getJson());
        self::assertSame('{}', $requests[2]->getBody());
    }

    public function testTypoInParameterNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('extnded');
        self::client(self::okTransport())->egrul->get('7707083893', ['extnded' => true]);
    }

    /** @return iterable<string, array{string, string}> */
    public static function liveFixtures(): iterable
    {
        yield 'egrul' => ['egrul', 'CompanyLookupResult'];
        yield 'testdata' => ['testdata', 'TestDataResponse'];
        yield 'aichat' => ['aichat', 'AiChatResponse'];
        yield 'cron' => ['cron', 'CronEvaluationResult'];
        yield 'dns' => ['dns', 'DnsLookupResponse'];
    }

    /** @dataProvider liveFixtures */
    public function testLiveFixtureFieldsAreDescribedInSpec(string $service, string $schema): void
    {
        $spec = json_decode((string) file_get_contents(__DIR__ . '/../spec/' . $service . '_ru.json'), true);
        self::assertIsArray($spec);
        $described = array_keys($spec['components']['schemas'][$schema]['properties']);
        self::assertSame([], array_values(array_diff(array_keys(self::fixture($service)), $described)));
    }

    public function testResponseFromLiveFixture(): void
    {
        $client = self::client(new MockTransport(Response::json(200, self::fixture('egrul'))));
        $card = $client->egrul->get('7707083893');
        self::assertSame('7707083893', $card['inn']);
        self::assertSame('62.01', $card['primaryOkved']['code']);
    }

    public function testExcerptReturnsPdfBytes(): void
    {
        $client = self::client(new MockTransport(new Response(200, ['Content-Type' => 'application/pdf'], '%PDF-1.7')));
        self::assertSame('%PDF-1.7', $client->egrul->getExcerpt('7707083893'));
    }

    public function testTestdataCsvReturnsString(): void
    {
        $csv = "fullName;innPerson\nИванов Иван;026881836682\n";
        $transport = new MockTransport();
        $transport->setDefault(static function (Request $request) use ($csv): Response {
            return new Response(200, ['Content-Type' => 'text/csv; charset=utf-8'], $csv);
        });
        $client = self::client($transport);
        self::assertSame($csv, $client->testdata->generate(['format' => 'csv', 'count' => 1]));
        self::assertSame($csv, $client->testdata->generateWithBody(['format' => 'csv']));
        $requests = $transport->getRequests();
        self::assertSame(['count' => '1', 'format' => 'csv'], $requests[0]->getQuery());
        self::assertSame(['format' => 'csv'], $requests[1]->getJson());
    }

    public function testTestdataJsonAndSeedHeader(): void
    {
        $fixture = self::fixture('testdata');
        $transport = new MockTransport();
        $transport->setDefault(static function (Request $request) use ($fixture): Response {
            return Response::json(200, $fixture, ['X-Atlorium-Seed' => '42']);
        });
        $client = self::client($transport);
        $result = $client->testdata->generate(['count' => 2, 'seed' => 42]);
        self::assertIsArray($result);
        self::assertNotEmpty($result['records'][0]['fullName']);
        self::assertSame('42', $client->testdata->withRawResponse()->generate(['count' => 2])->getHeader('X-Atlorium-Seed'));
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function deleteSessionBodies(): iterable
    {
        yield 'JSON-строка' => ['"Сессия удалена."', 'application/json; charset=utf-8', 'Сессия удалена.'];
        yield 'простой текст' => ['Сессия удалена.', 'text/plain; charset=utf-8', 'Сессия удалена.'];
        yield 'JSON-объект с message' => ['{"message":"Сессия удалена."}', 'application/json', 'Сессия удалена.'];
        yield 'JSON-объект без message' => ['{"deleted":true}', 'application/json', '{"deleted":true}'];
        yield 'пустое тело' => ['', 'text/plain', ''];
        yield 'JSON null' => ['null', 'application/json', ''];
    }

    /** @dataProvider deleteSessionBodies */
    public function testDeleteSessionReturnsString(string $content, string $contentType, string $expected): void
    {
        $client = self::client(new MockTransport(new Response(200, ['Content-Type' => $contentType], $content)));
        self::assertSame($expected, $client->aichat->deleteSession('abc'));
    }

    public function testResourceErrorsAreTyped(): void
    {
        $body = ['error' => 'not_found', 'title' => 'Ничего не найдено', 'message' => 'Организация не найдена в реестре'];
        $client = self::client(new MockTransport(Response::json(404, $body)));
        try {
            $client->egrul->get('0000000000');
            self::fail('Ожидалось исключение');
        } catch (NotFoundException $error) {
            self::assertSame('not_found', $error->getErrorCode());
            self::assertStringContainsString('не найдена', $error->getMessage());
        }
    }

    /** @return iterable<string, array{string, string, list<mixed>, float}> */
    public static function operationTimeouts(): iterable
    {
        yield 'egrul.get' => ['egrul', 'get', ['7707083893'], 30.0];
        yield 'ocr.recognize' => ['ocr', 'recognize', ["\x89PNG"], 120.0];
        yield 'pagespeed.audit' => ['pagespeed', 'audit', ['https://a.ru'], 120.0];
        yield 'pagespeed.auditBatch' => ['pagespeed', 'auditBatch', [['https://a.ru']], 600.0];
        yield 'aichat.send' => ['aichat', 'send', ['Привет'], 660.0];
        yield 'imagecheck.analyze' => ['imagecheck', 'analyze', ["\x89PNG"], 60.0];
    }

    /**
     * @dataProvider operationTimeouts
     *
     * @param list<mixed> $arguments
     */
    public function testOperationDefaultTimeouts(string $resource, string $method, array $arguments, float $expected): void
    {
        $transport = self::okTransport();
        self::client($transport)->{$resource}->{$method}(...$arguments);
        self::assertSame($expected, $transport->getLastRequest()->getTimeout());
    }

    public function testClientAndCallTimeoutsOverrideOperationMinimum(): void
    {
        $transport = self::okTransport();
        $client = self::client($transport, ['timeout' => 900]);
        $client->ocr->recognize("\x89PNG");
        $client->ocr->recognize("\x89PNG", ['timeout' => 5]);
        $requests = $transport->getRequests();
        self::assertSame(900.0, $requests[0]->getTimeout());
        self::assertSame(5.0, $requests[1]->getTimeout());
    }

    public function testImagesFromPathFileInfoStreamAndBase64(): void
    {
        $path = __DIR__ . '/fixtures/white.png';
        $expected = base64_encode((string) file_get_contents($path));
        $stream = fopen($path, 'rb');
        self::assertIsResource($stream);

        $transport = self::okTransport();
        $client = self::client($transport);
        $client->ocr->recognize($path);
        $client->ocr->recognize(new \SplFileInfo($path));
        $client->imagecheck->analyze($stream, ['includeText' => false]);
        $client->imagecheck->analyze(ImageInput::fromBase64($expected));
        fclose($stream);

        $requests = $transport->getRequests();
        self::assertSame(['image' => $expected], $requests[0]->getJson());
        self::assertSame(['image' => $expected], $requests[1]->getJson());
        self::assertSame(['image' => $expected, 'includeText' => false], $requests[2]->getJson());
        self::assertSame(['image' => $expected], $requests[3]->getJson());
    }

    public function testMissingImageFileGivesClearError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('не найден');
        self::client(self::okTransport())->ocr->recognize('scan-that-does-not-exist.png');
    }

    public function testResourcesAreCachedAndBoundToTheirClient(): void
    {
        $transport = self::okTransport();
        $client = self::client($transport);
        self::assertSame($client->egrul, $client->egrul);
        self::assertSame($client->egrul->withRawResponse(), $client->egrul->withRawResponse());
        $copy = $client->withOptions(['maxRetries' => 1]);
        self::assertNotSame($client->egrul, $copy->egrul);
        $copy->egrul->get('7707083893');
        self::assertCount(1, $transport->getRequests());
    }

    public function testCronEnumsMatchServerValues(): void
    {
        self::assertSame(0, CronTemplateType::EVERY_MINUTE);
        self::assertSame(4, CronTemplateType::DAILY);
        self::assertSame(6, CronTemplateType::MONTHLY);
        self::assertSame(0, DayOfWeek::SUNDAY);
        self::assertSame(6, DayOfWeek::SATURDAY);
    }
}
