<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * HTTP 5xx: ошибка на стороне сервиса. Деньги за такой ответ не списываются.
 *
 * 503 — отдельный наследник {@see ServiceUnavailableException} (его SDK повторяет); прочие 5xx
 * не повторяются.
 */
class ServerException extends AtloriumException
{
}
