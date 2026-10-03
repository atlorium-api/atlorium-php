<?php

declare(strict_types=1);

namespace Atlorium;

use Atlorium\Exception\AtloriumException;
use Atlorium\Exception\ConnectionException;
use Atlorium\Http\CurlTransport;
use Atlorium\Http\Request;
use Atlorium\Http\Response;
use Atlorium\Http\TransportInterface;

/**
 * Транспортное ядро клиента: ключ, заголовки, сериализация параметров, ретраи, разбор ответов.
 *
 * Напрямую не создаётся — используйте {@see Client}.
 */
abstract class BaseClient
{
    public const VERSION = '0.1.0';

    /** Публичный демо-ключ. С ним API отвечает детерминированными моками, а не реальными данными. */
    public const SANDBOX_API_KEY = 'ak_sandbox_demo_mockdata_v1';

    public const DEFAULT_BASE_URL = 'https://atlorium.com';

    /** Таймаут запроса по умолчанию, секунд. */
    public const DEFAULT_TIMEOUT = 30.0;

    public const DEFAULT_MAX_RETRIES = 2;

    /**
     * Потолок ожидания перед повтором, секунд. Исчерпав часовой лимит, сервер честно просит
     * подождать 40+ минут — ждать столько внутри вызова нельзя, поэтому такой ответ сразу
     * превращается в исключение (см. getRetryAfter()).
     */
    public const MAX_RETRY_DELAY = 120.0;

    /**
     * Потолок ожидания перед повтором при обработке веб-запроса (PHP-FPM, Apache), секунд. Страница
     * сайта не должна висеть минутами: nginx по умолчанию обрывает ответ через 60 с. Для CLI
     * (cron, агенты Битрикса, очереди) действует {@see MAX_RETRY_DELAY}. Меняется настройкой maxRetryDelay.
     */
    public const MAX_RETRY_DELAY_WEB = 10.0;

    private const INITIAL_RETRY_DELAY = 0.5;

    private const MAX_BACKOFF_DELAY = 8.0;

    /** Ключи настроек вызова, которые можно передать в массиве параметров любого метода. */
    private const CALL_OPTION_KEYS = ['timeout', 'maxRetries', 'headers'];

    private const CLIENT_OPTION_KEYS = ['baseUrl', 'timeout', 'maxRetries', 'maxRetryDelay', 'transport', 'headers', 'sleeper'];

    /** Предупреждение о демо-режиме выводится один раз за процесс. */
    private static bool $sandboxWarned = false;

    private string $apiKey;

    private string $baseUrl;

    private float $timeout;

    private int $maxRetries;

    private float $maxRetryDelay;

    private TransportInterface $transport;

    /** @var array<string, string> */
    private array $defaultHeaders;

    /** @var \Closure(float): void */
    private \Closure $sleeper;

    /**
     * @param string|null          $apiKey  API-ключ. Если не задан — переменная окружения ATLORIUM_API_KEY,
     *                                      а если нет и её — публичный демо-ключ (ответы-моки, см. isSandbox()).
     * @param array<string, mixed> $options Настройки:
     *                                      - baseUrl (string) — адрес API, по умолчанию https://atlorium.com
     *                                      или ATLORIUM_BASE_URL;
     *                                      - timeout (float, секунды) — таймаут запроса, по умолчанию 30;
     *                                      у тяжёлых операций свой минимум (OCR и аудит сайта — 120 с,
     *                                      ИИ-чат — 660 с);
     *                                      - maxRetries (int) — повторы на 429, 503 (кроме плановых работ) и сетевых
     *                                      ошибках, по умолчанию 2;
     *                                      - maxRetryDelay (float, секунды) — сколько максимум ждать перед повтором,
     *                                      если сервер просит подождать (Retry-After); дольше — сразу исключение.
     *                                      По умолчанию 120 в CLI и 10 при обработке веб-запроса;
     *                                      - transport (TransportInterface) — свой транспорт: Psr18Transport,
     *                                      MockTransport для тестов или CurlTransport с опциями прокси;
     *                                      - headers (array<string, string>) — дополнительные заголовки;
     *                                      - sleeper (callable(float): void) — пауза между попытками (для тестов).
     */
    public function __construct(
        #[\SensitiveParameter]
        ?string $apiKey = null,
        array $options = []
    ) {
        foreach (array_keys($options) as $key) {
            if (!in_array($key, self::CLIENT_OPTION_KEYS, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Неизвестная настройка клиента «%s». Допустимые: %s.',
                    $key,
                    implode(', ', self::CLIENT_OPTION_KEYS)
                ));
            }
        }

        // Ключ — в приватном поле: он не попадает ни в var_dump/print_r, ни в тексты исключений.
        $resolvedKey = self::nonEmpty($apiKey) ?? self::env('ATLORIUM_API_KEY');
        if ($resolvedKey === null) {
            // Молча перейти на моки в проде опасно: проверки контрагентов вернут выдуманные данные.
            self::warnImplicitSandbox();
        }
        $this->apiKey = $resolvedKey ?? self::SANDBOX_API_KEY;

        $baseUrlOption = $options['baseUrl'] ?? null;
        $baseUrl = null;
        if (is_string($baseUrlOption)) {
            $baseUrl = self::nonEmpty($baseUrlOption);
        } elseif ($baseUrlOption !== null) {
            throw new \InvalidArgumentException('Настройка baseUrl должна быть строкой.');
        }
        $this->baseUrl = rtrim($baseUrl ?? self::env('ATLORIUM_BASE_URL') ?? self::DEFAULT_BASE_URL, '/');

        $this->timeout = self::positiveSeconds($options['timeout'] ?? self::DEFAULT_TIMEOUT, 'timeout');
        $this->maxRetries = self::retries($options['maxRetries'] ?? self::DEFAULT_MAX_RETRIES);
        $this->maxRetryDelay = array_key_exists('maxRetryDelay', $options)
            ? self::nonNegativeSeconds($options['maxRetryDelay'], 'maxRetryDelay')
            : (in_array(PHP_SAPI, ['cli', 'phpdbg'], true) ? self::MAX_RETRY_DELAY : self::MAX_RETRY_DELAY_WEB);

        $transport = $options['transport'] ?? null;
        if ($transport instanceof TransportInterface) {
            $this->transport = $transport;
        } elseif ($transport === null) {
            $this->transport = new CurlTransport();
        } else {
            throw new \InvalidArgumentException('Настройка transport должна реализовывать ' . TransportInterface::class . '.');
        }

        $this->defaultHeaders = self::headerMap($options['headers'] ?? []);

        $sleeper = $options['sleeper'] ?? null;
        if ($sleeper === null) {
            $this->sleeper = static function (float $seconds): void {
                usleep((int) round($seconds * 1000000));
            };
        } elseif (is_callable($sleeper)) {
            $this->sleeper = static function (float $seconds) use ($sleeper): void {
                $sleeper($seconds);
            };
        } else {
            throw new \InvalidArgumentException('Настройка sleeper должна быть callable.');
        }
    }

    /** true, если клиент работает на публичном демо-ключе (ответы — моки). */
    public function isSandbox(): bool
    {
        return $this->apiKey === self::SANDBOX_API_KEY;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /** Таймаут запроса по умолчанию, секунд. */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /** Сколько максимум ждать перед повтором, если сервер просит подождать, секунд. */
    public function getMaxRetryDelay(): float
    {
        return $this->maxRetryDelay;
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * Копия клиента с другими таймаутом, числом повторов и/или заголовками. Ключ, адрес и транспорт — те же.
     *
     * @param array<string, mixed> $options timeout, maxRetries, maxRetryDelay, headers
     *
     * @return static
     */
    public function withOptions(array $options)
    {
        $copy = clone $this;
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'timeout':
                    $copy->timeout = self::positiveSeconds($value, 'timeout');
                    break;
                case 'maxRetries':
                    $copy->maxRetries = self::retries($value);
                    break;
                case 'maxRetryDelay':
                    $copy->maxRetryDelay = self::nonNegativeSeconds($value, 'maxRetryDelay');
                    break;
                case 'headers':
                    $copy->defaultHeaders = self::headerMap($value);
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf(
                        'withOptions принимает только timeout, maxRetries, maxRetryDelay и headers, получено «%s».',
                        $key
                    ));
            }
        }

        return $copy;
    }

    /**
     * Скрывает API-ключ в var_dump() и print_r().
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'baseUrl' => $this->baseUrl,
            'isSandbox' => $this->isSandbox(),
            'timeout' => $this->timeout,
            'maxRetries' => $this->maxRetries,
            'maxRetryDelay' => $this->maxRetryDelay,
            'transport' => get_class($this->transport),
        ];
    }

    /**
     * Выполняет вызов API. Используется ресурсами; напрямую обычно не нужен.
     *
     * @internal
     *
     * @param array{
     *     method: string,
     *     path: string,
     *     query?: array<string, mixed>,
     *     optionalQuery?: list<string>,
     *     body?: array<string, mixed>,
     *     optionalBody?: list<string>,
     *     bodyLists?: list<string>,
     *     json?: list<mixed>,
     *     defaultTimeout?: float,
     *     responseType?: string
     * } $spec описание операции
     * @param array<array-key, mixed> $params необязательные параметры пользователя и настройки вызова
     *
     * @throws AtloriumException
     */
    public function send(array $spec, array $params = []): ApiResponse
    {
        $optionalQuery = $spec['optionalQuery'] ?? [];
        $optionalBody = $spec['optionalBody'] ?? [];
        $allowed = array_merge($optionalQuery, $optionalBody, self::CALL_OPTION_KEYS);
        foreach (array_keys($params) as $key) {
            if (!is_string($key) || !in_array($key, $allowed, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Неизвестный параметр «%s». Допустимые: %s.',
                    (string) $key,
                    implode(', ', $allowed)
                ));
            }
        }

        $query = $spec['query'] ?? [];
        foreach ($optionalQuery as $key) {
            if (array_key_exists($key, $params)) {
                $query[$key] = $params[$key];
            }
        }

        $body = null;
        if (array_key_exists('json', $spec)) {
            $body = self::encodeJson(self::normalizeJson($spec['json']));
        } elseif (array_key_exists('body', $spec)) {
            $fields = $spec['body'];
            $lists = $spec['bodyLists'] ?? [];
            foreach ($optionalBody as $key) {
                // Непереданный параметр не отправляется — тогда действует серверный дефолт.
                if (!array_key_exists($key, $params) || $params[$key] === null) {
                    continue;
                }
                $value = $params[$key];
                $fields[$key] = in_array($key, $lists, true) && is_string($value) ? [$value] : $value;
            }
            $body = self::encodeJson($fields === [] ? new \stdClass() : self::normalizeJson($fields));
        }

        $timeout = array_key_exists('timeout', $params)
            ? self::positiveSeconds($params['timeout'], 'timeout')
            : max($this->timeout, $spec['defaultTimeout'] ?? 0.0);
        $maxRetries = array_key_exists('maxRetries', $params) ? self::retries($params['maxRetries']) : $this->maxRetries;
        $callHeaders = array_key_exists('headers', $params) ? self::headerMap($params['headers']) : [];

        $headers = ['Accept' => 'application/json', 'User-Agent' => sprintf('atlorium-php/%s (php/%s)', self::VERSION, PHP_VERSION)];
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
        }
        // Ключ ставится последним: заголовки вызова его не перекрывают, в каком бы регистре ни было имя.
        $headers = self::mergeHeaders($headers, $this->defaultHeaders, $callHeaders, ['Authorization' => 'Bearer ' . $this->apiKey]);

        $request = new Request($spec['method'], $this->buildUrl($spec['path'], $query), $headers, $body, $timeout);

        return $this->execute($request, $maxRetries, $spec['responseType'] ?? 'json');
    }

    /**
     * Приводит значение параметра строки запроса к виду, который понимает сервер.
     *
     * @param mixed $value
     */
    public static function serializeQueryValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_string($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            return self::formatFloat($value);
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                $parts[] = self::serializeQueryValue($item);
            }

            return implode(',', $parts);
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            $string = \call_user_func([$value, '__toString']);
            if (is_string($string)) {
                return $string;
            }
        }

        throw new \InvalidArgumentException('Значение параметра типа ' . gettype($value) . ' нельзя передать в строке запроса.');
    }

    /** @param array<string, mixed> $query */
    private function buildUrl(string $path, array $query): string
    {
        $pairs = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            $pairs[] = rawurlencode($key) . '=' . rawurlencode(self::serializeQueryValue($value));
        }

        return $this->baseUrl . $path . ($pairs === [] ? '' : '?' . implode('&', $pairs));
    }

    private function execute(Request $request, int $maxRetries, string $responseType): ApiResponse
    {
        $attempt = 0;
        while (true) {
            try {
                $response = $this->transport->send($request);
            } catch (ConnectionException $error) {
                // GET и DELETE повторяются при любой сетевой ошибке, POST — только если запрос не ушёл:
                // POST-операции платные, и повтор после таймаута мог бы выполнить операцию второй раз.
                $safe = $request->getMethod() !== 'POST' || $error->isRequestNotSent();
                if ($safe && $attempt < $maxRetries) {
                    ($this->sleeper)(self::backoffDelay($attempt));
                    ++$attempt;
                    continue;
                }
                throw $error;
            }

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return new ApiResponse(self::parseBody($response, $responseType), $response);
            }

            $error = AtloriumException::fromResponse($response);
            $retryAfter = $error->getRetryAfter();
            // 503 с кодом service_disabled / maintenance — плановые работы: повтор через секунду бесполезен.
            $retryable = $status === 429 || ($status === 503 && !self::isMaintenance($error));
            // Сервер просит ждать дольше разумного — не вешаем вызов, отдаём ошибку сразу.
            if ($retryable && $attempt < $maxRetries && ($retryAfter === null || $retryAfter <= $this->maxRetryDelay)) {
                ($this->sleeper)($retryAfter ?? self::backoffDelay($attempt));
                ++$attempt;
                continue;
            }

            throw $error;
        }
    }

    private static function isMaintenance(AtloriumException $error): bool
    {
        return in_array($error->getErrorCode(), ['service_disabled', 'maintenance'], true);
    }

    /** @return mixed */
    private static function parseBody(Response $response, string $responseType)
    {
        $body = $response->getBody();
        if ($responseType === 'bytes' || $responseType === 'text') {
            return $body;
        }
        if ($body === '') {
            // Пустое тело: у JSON-ответа это null, у ответа «текст или JSON» — пустая строка.
            return $responseType === 'auto' ? '' : null;
        }
        if ($responseType === 'auto' && !self::isJsonContentType($response->getHeader('Content-Type'))) {
            // Сервер вернул не JSON (например, CSV или простой текст) — отдаём строкой.
            return $body;
        }

        $data = json_decode($body, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new AtloriumException(
                sprintf('HTTP %d: сервер вернул некорректный JSON (%s).', $response->getStatusCode(), json_last_error_msg()),
                $response->getStatusCode(),
                $body,
                $response->getHeaders()
            );
        }

        return $data;
    }

    private static function isJsonContentType(?string $value): bool
    {
        $type = (string) $value;
        $semicolon = strpos($type, ';');
        if ($semicolon !== false) {
            $type = (string) substr($type, 0, $semicolon);
        }
        $type = strtolower(trim($type));

        return $type === 'application/json' || $type === 'text/json' || preg_match('~\+json$~', $type) === 1;
    }

    private static function backoffDelay(int $attempt): float
    {
        $base = min(self::INITIAL_RETRY_DELAY * (2 ** $attempt), self::MAX_BACKOFF_DELAY);

        return $base * (0.75 + mt_rand() / mt_getrandmax() * 0.5);
    }

    /**
     * Числа — без зависимости от локали: в PHP 7.4 (string) 55.75 при ru_RU даёт «55,75»,
     * а 1С-Битрикс ставит именно такую локаль. json_encode от локали не зависит.
     */
    private static function formatFloat(float $value): string
    {
        if (!is_finite($value)) {
            throw new \InvalidArgumentException('Число должно быть конечным.');
        }
        $encoded = json_encode($value);

        return $encoded === false ? sprintf('%.14F', $value) : $encoded;
    }

    /**
     * Готовит значение к JSON: даты — в строку ISO 8601.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private static function normalizeJson($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = self::normalizeJson($item);
            }

            return $result;
        }

        return $value;
    }

    /** @param mixed $value */
    private static function encodeJson($value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if ($json === false) {
            throw new \InvalidArgumentException(
                'Не удалось закодировать тело запроса в JSON: ' . json_last_error_msg() . '. Строки должны быть в UTF-8: '
                . 'на сайте в windows-1251 перекодируйте их, например mb_convert_encoding($text, \'UTF-8\', \'Windows-1251\').'
            );
        }

        return $json;
    }

    /** @param mixed $value */
    private static function positiveSeconds($value, string $name): float
    {
        if ((!is_int($value) && !is_float($value)) || $value <= 0) {
            throw new \InvalidArgumentException(sprintf('Настройка %s должна быть положительным числом секунд.', $name));
        }

        return (float) $value;
    }

    /** @param mixed $value */
    private static function nonNegativeSeconds($value, string $name): float
    {
        if ((!is_int($value) && !is_float($value)) || $value < 0) {
            throw new \InvalidArgumentException(sprintf('Настройка %s должна быть неотрицательным числом секунд.', $name));
        }

        return (float) $value;
    }

    private static function warnImplicitSandbox(): void
    {
        if (self::$sandboxWarned) {
            return;
        }
        self::$sandboxWarned = true;
        error_log(
            'Atlorium: API-ключ не задан (ни аргументом, ни переменной ATLORIUM_API_KEY) — клиент работает на '
            . 'публичном демо-ключе, ответы API — моки, а не реальные данные. Чтобы явно выбрать демо-режим без '
            . 'предупреждения, передайте ключ \Atlorium\Client::SANDBOX_API_KEY.'
        );
    }

    /** @param mixed $value */
    private static function retries($value): int
    {
        if (!is_int($value) || $value < 0) {
            throw new \InvalidArgumentException('Настройка maxRetries должна быть целым неотрицательным числом.');
        }

        return $value;
    }

    /**
     * Объединяет наборы заголовков без учёта регистра имени: более поздний набор перекрывает ранний.
     *
     * @param array<string, string> ...$sets
     *
     * @return array<string, string>
     */
    private static function mergeHeaders(array ...$sets): array
    {
        $merged = [];
        // Имя в нижнем регистре => имя, под которым заголовок лежит в $merged.
        $names = [];
        foreach ($sets as $set) {
            foreach ($set as $name => $value) {
                $name = (string) $name;
                $lower = strtolower($name);
                if (isset($names[$lower])) {
                    unset($merged[$names[$lower]]);
                }
                $names[$lower] = $name;
                $merged[$name] = $value;
            }
        }

        return $merged;
    }

    /**
     * @param mixed $value
     *
     * @return array<string, string>
     */
    private static function headerMap($value): array
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Настройка headers должна быть массивом «имя => значение».');
        }
        $headers = [];
        foreach ($value as $name => $header) {
            if (!is_string($name) || !is_scalar($header)) {
                throw new \InvalidArgumentException('Настройка headers должна быть массивом «имя => значение».');
            }
            $headers[$name] = (string) $header;
        }

        return $headers;
    }

    private static function nonEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);
        if (is_string($value)) {
            return self::nonEmpty($value);
        }
        $fromArray = $_ENV[$name] ?? $_SERVER[$name] ?? null;

        return is_string($fromArray) ? self::nonEmpty($fromArray) : null;
    }
}
