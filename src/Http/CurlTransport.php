<?php

declare(strict_types=1);

namespace Atlorium\Http;

use Atlorium\Exception\ConnectionException;
use Atlorium\Exception\TimeoutException;

/**
 * Транспорт по умолчанию — на расширении cURL. Зависимостей не требует.
 *
 * Дополнительные опции cURL (прокси, свои сертификаты на старых хостингах) передаются в конструктор:
 *
 *     $client = new \Atlorium\Client(null, [
 *         'transport' => new CurlTransport([CURLOPT_PROXY => 'http://proxy.local:3128']),
 *     ]);
 */
final class CurlTransport implements TransportInterface
{
    /** Ошибки cURL, при которых соединение не установилось и запрос не ушёл на сервер. */
    private const NOT_SENT_ERRORS = [CURLE_COULDNT_RESOLVE_PROXY, CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_CONNECT];

    /** @var array<int, mixed> */
    private array $curlOptions;

    /** @param array<int, mixed> $curlOptions дополнительные опции CURLOPT_*, применяются поверх стандартных */
    public function __construct(array $curlOptions = [])
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException(
                'Для работы SDK Atlorium нужно расширение PHP curl — или передайте свой transport, например Psr18Transport.'
            );
        }
        $this->curlOptions = $curlOptions;
    }

    public function send(Request $request): Response
    {
        $handle = curl_init();
        if ($handle === false) {
            throw new ConnectionException('Не удалось инициализировать cURL.');
        }

        $headers = [];
        foreach ($request->getHeaders() as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        // Без этого cURL на телах больше 1 КБ ждёт «100 Continue» и теряет до секунды.
        $headers[] = 'Expect:';

        $responseHeaders = [];
        $timeoutMs = max(1, (int) ceil($request->getTimeout() * 1000));

        $options = [
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_ENCODING => '',
            CURLOPT_NOSIGNAL => true,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_CONNECTTIMEOUT_MS => min($timeoutMs, 10000),
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $trimmed = trim($line);
                if (stripos($trimmed, 'HTTP/') === 0) {
                    // Начался новый ответ (например, после «100 Continue») — прежние заголовки не нужны.
                    $responseHeaders = [];
                } else {
                    $parts = explode(':', $trimmed, 2);
                    if (count($parts) === 2) {
                        $responseHeaders[strtolower(trim($parts[0]))][] = trim($parts[1]);
                    }
                }

                // cURL ждёт длину строки в байтах, а при mbstring.func_overload (PHP 7) strlen() считает символы.
                return self::byteLength($line);
            },
        ];
        $body = $request->getBody();
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        foreach ($this->curlOptions as $option => $value) {
            $options[$option] = $value;
        }

        if (!curl_setopt_array($handle, $options)) {
            $message = curl_error($handle);
            self::close($handle);
            throw new ConnectionException('Некорректные опции cURL: ' . $message);
        }

        $content = curl_exec($handle);
        $errno = curl_errno($handle);
        $message = curl_error($handle);
        $status = self::intInfo(curl_getinfo($handle, CURLINFO_RESPONSE_CODE));
        $notSent = self::isRequestNotSent(
            $errno,
            self::floatInfo(curl_getinfo($handle, CURLINFO_CONNECT_TIME)),
            self::floatInfo(curl_getinfo($handle, CURLINFO_PRETRANSFER_TIME)),
            self::intInfo(curl_getinfo($handle, CURLINFO_REQUEST_SIZE)),
            $status
        );
        self::close($handle);

        if ($errno !== 0 || !is_string($content)) {
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                $text = sprintf(
                    'Истекло время ожидания ответа API Atlorium (%s с).',
                    rtrim(rtrim(sprintf('%.3F', $request->getTimeout()), '0'), '.')
                );
                // Таймаут подключения — запрос не ушёл, и его можно повторить; таймаут чтения — нет.
                throw $notSent ? TimeoutException::connectTimeout($text) : new TimeoutException($text);
            }
            $text = sprintf('Не удалось подключиться к API Atlorium: %s (cURL %d).', $message, $errno);
            if ($notSent) {
                throw ConnectionException::requestNotSent($text);
            }
            throw new ConnectionException($text);
        }

        return new Response($status, $responseHeaders, $content);
    }

    /**
     * Дошёл ли запрос до сервера, по данным cURL после ошибки. «Не дошёл» — только когда это
     * известно наверняка: DNS, отказ в соединении или соединение так и не установилось (нет ни
     * времени подключения, ни отправленных байтов, ни ответа). В остальных случаях считается,
     * что запрос мог выполниться.
     *
     * @internal
     *
     * @param int   $errno           код ошибки cURL (0 — ошибки нет)
     * @param float $connectTime     CURLINFO_CONNECT_TIME, секунды
     * @param float $preTransferTime CURLINFO_PRETRANSFER_TIME, секунды
     * @param int   $requestSize     CURLINFO_REQUEST_SIZE, байты
     * @param int   $status          HTTP-статус ответа, 0 — ответа не было
     */
    public static function isRequestNotSent(int $errno, float $connectTime, float $preTransferTime, int $requestSize, int $status): bool
    {
        if ($errno === 0) {
            return false;
        }
        if (in_array($errno, self::NOT_SENT_ERRORS, true)) {
            return true;
        }

        return $connectTime <= 0.0 && $preTransferTime <= 0.0 && $requestSize <= 0 && $status === 0;
    }

    /** @param mixed $value результат curl_getinfo() */
    private static function intInfo($value): int
    {
        return is_int($value) ? $value : (is_float($value) ? (int) $value : 0);
    }

    /** @param mixed $value результат curl_getinfo() */
    private static function floatInfo($value): float
    {
        return is_float($value) || is_int($value) ? (float) $value : 0.0;
    }

    private static function byteLength(string $value): int
    {
        return extension_loaded('mbstring') ? (int) mb_strlen($value, '8bit') : strlen($value);
    }

    /**
     * Освобождает дескриптор cURL. С PHP 8.0 curl_close() ничего не делает (дескриптор закрывается
     * сам), а в PHP 8.5 объявлена устаревшей — поэтому вызывается только на PHP 7.
     *
     * @param mixed $handle
     */
    private static function close($handle): void
    {
        // Условие всегда истинно для анализатора, настроенного на PHP 7.4, но не во время выполнения на PHP 8.
        // @phpstan-ignore-next-line
        if (PHP_VERSION_ID < 80000 && is_resource($handle)) {
            curl_close($handle);
        }
    }
}
