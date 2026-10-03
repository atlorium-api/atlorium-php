<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * HTTP 400: неверный формат входных данных. Не ретраится — повтор с теми же данными
 * даст тот же результат. Отклонённый запрос не тарифицируется.
 */
class ValidationException extends AtloriumException
{
}
