<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * HTTP 429: превышен лимит запросов. SDK сам повторяет запрос с учётом Retry-After;
 * исключение означает, что попытки исчерпаны. Через сколько секунд повторять —
 * {@see AtloriumException::getRetryAfter()}.
 */
class RateLimitException extends AtloriumException
{
}
