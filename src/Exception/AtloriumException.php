<?php

declare(strict_types=1);

namespace Atlorium\Exception;

use Atlorium\Http\Headers;
use Atlorium\Http\Response;

/**
 * Базовое исключение SDK Atlorium.
 *
 * Для ответов с HTTP-ошибкой содержит статус, тело, заголовки и разобранную структуру
 * ApiError (поля error/title/message/balance/retryAfterSeconds). Для сетевых ошибок
 * статус равен null. API-ключ в сообщение и поля исключения никогда не попадает.
 *
 * Иерархия: 400 {@see ValidationException}, 401 {@see AuthenticationException},
 * 402 {@see InsufficientCreditsException}, 403 {@see PermissionDeniedException},
 * 404 {@see NotFoundException}, 429 {@see RateLimitException}, 5xx {@see ServerException}
 * (503 — его наследник {@see ServiceUnavailableException}), сеть {@see ConnectionException} /
 * {@see TimeoutException}. Прочие коды (405, 415 и т.п.) — сам AtloriumException.
 */
class AtloriumException extends \RuntimeException
{
    /** @var array<int, string> */
    private const REASONS = [
        400 => 'Неверный формат входных данных',
        401 => 'API-ключ отсутствует, просрочен или недействителен',
        402 => 'Недостаточно кредитов на балансе — пополните на https://atlorium.com',
        403 => 'Сервис не входит в условия вашей учётной записи. Чтобы подключить его, напишите на support@atlorium.com',
        404 => 'Объект не найден либо маршрут не существует',
        429 => 'Превышен лимит запросов — повторите позже',
        503 => 'Сервис временно недоступен: сбой внешнего источника данных или плановые работы '
            . '(состояние — https://atlorium.com/status). Деньги за такой ответ не списываются',
    ];

    private ?int $statusCode;

    private string $body;

    /** @var array<string, list<string>> */
    private array $headers;

    /** @var array<array-key, mixed>|null */
    private ?array $errorBody;

    /**
     * @param string                  $message    человекочитаемое сообщение (по-русски)
     * @param int|null                $statusCode HTTP-статус или null для сетевых ошибок
     * @param string                  $body       тело ответа как есть
     * @param array<array-key, mixed> $headers    заголовки ответа
     */
    public function __construct(
        string $message,
        ?int $statusCode = null,
        string $body = '',
        array $headers = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = Headers::normalize($headers);
        $this->errorBody = self::decodeObject($body);
    }

    /** Создаёт исключение нужного класса по HTTP-ответу с ошибкой. */
    public static function fromResponse(Response $response): self
    {
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $headers = $response->getHeaders();
        $message = self::buildMessage($status, $body);

        switch ($status) {
            case 400:
                return new ValidationException($message, $status, $body, $headers);
            case 401:
                return new AuthenticationException($message, $status, $body, $headers);
            case 402:
                return new InsufficientCreditsException($message, $status, $body, $headers);
            case 403:
                return new PermissionDeniedException($message, $status, $body, $headers);
            case 404:
                return new NotFoundException($message, $status, $body, $headers);
            case 429:
                return new RateLimitException($message, $status, $body, $headers);
            case 503:
                return new ServiceUnavailableException($message, $status, $body, $headers);
        }

        if ($status >= 500) {
            return new ServerException($message, $status, $body, $headers);
        }

        return new self($message, $status, $body, $headers);
    }

    /** HTTP-статус ответа; null — ответа не было (сетевая ошибка). */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /** Тело ответа как есть (обычно JSON со структурой ApiError). */
    public function getBody(): string
    {
        return $this->body;
    }

    /** @return array<string, list<string>> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return Headers::get($this->headers, $name);
    }

    /** Идентификатор запроса, если сервер его прислал (X-Request-Id). */
    public function getRequestId(): ?string
    {
        return $this->getHeader('X-Request-Id') ?? $this->getHeader('Request-Id');
    }

    /**
     * Разобранное тело ошибки (структура ApiError) или null, если тело не JSON-объект.
     *
     * @return array<array-key, mixed>|null
     */
    public function getErrorBody(): ?array
    {
        return $this->errorBody;
    }

    /** Машинный код причины из поля error: insufficient_credits, rate_limited_client и т.п. */
    public function getErrorCode(): ?string
    {
        return $this->stringField('error');
    }

    /** Короткий заголовок ошибки от сервера (поле title). */
    public function getTitle(): ?string
    {
        return $this->stringField('title');
    }

    /** Подробное объяснение от сервера (поле message). */
    public function getServerMessage(): ?string
    {
        return $this->stringField('message');
    }

    /**
     * Через сколько секунд имеет смысл повторить запрос: заголовок Retry-After, а если его
     * нет — поле retryAfterSeconds. null — повтор не поможет или сервер не подсказал.
     * Есть у любой ошибки с HTTP-ответом, не только у {@see RateLimitException}.
     */
    public function getRetryAfter(): ?float
    {
        $fromHeader = Headers::parseRetryAfter($this->getHeader('Retry-After'));
        if ($fromHeader !== null) {
            return $fromHeader;
        }

        $value = $this->errorBody['retryAfterSeconds'] ?? null;

        return is_int($value) || is_float($value) ? max(0.0, (float) $value) : null;
    }

    /** Строковое поле тела ошибки или null. */
    protected function stringField(string $name): ?string
    {
        $value = $this->errorBody[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    /** Числовое поле тела ошибки или null. */
    protected function floatField(string $name): ?float
    {
        $value = $this->errorBody[$name] ?? null;

        return is_int($value) || is_float($value) ? (float) $value : null;
    }

    private static function buildMessage(int $status, string $body): string
    {
        $reason = self::REASONS[$status]
            ?? ($status >= 500
                ? 'Внутренняя ошибка сервиса (за сбой на своей стороне мы не списываем деньги)'
                : 'Неожиданный ответ сервера');

        $decoded = self::decodeObject($body);
        $details = isset($decoded['message']) && is_string($decoded['message'])
            ? $decoded['message']
            : trim($body);

        $message = sprintf('HTTP %d: %s.', $status, $reason);
        if ($details !== '') {
            $message .= ' Ответ сервера: ' . self::truncate($details, 300);
        }

        return $message;
    }

    /** @return array<array-key, mixed>|null */
    private static function decodeObject(string $body): ?array
    {
        if ($body === '' || $body[0] !== '{') {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function truncate(string $text, int $limit): string
    {
        // Без ext-mbstring: регулярное выражение с /u режет по символам, а не по байтам.
        if (preg_match('/^.{0,' . $limit . '}/us', $text, $matches) === 1) {
            return $matches[0] === $text ? $text : $matches[0] . '…';
        }

        return (string) substr($text, 0, $limit);
    }
}
