<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * HTTP 401: API-ключ отсутствует, просрочен или недействителен. Не ретраится.
 */
class AuthenticationException extends AtloriumException
{
}
