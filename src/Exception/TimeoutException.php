<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * Сервер не ответил за отведённый таймаут. Наследник {@see ConnectionException}, поэтому
 * ловится и общим обработчиком сетевых ошибок. Ретраится автоматически в пределах maxRetries,
 * кроме POST: запрос мог дойти до сервера и выполниться. Исключение — таймаут подключения
 * ({@see isRequestNotSent()} = true): соединение не установилось, запрос не ушёл, и его можно
 * повторить для любого метода.
 */
class TimeoutException extends ConnectionException
{
    /** Таймаут подключения: соединение не установилось, запрос точно не ушёл на сервер. */
    public static function connectTimeout(string $message, ?\Throwable $previous = null): self
    {
        $error = new self($message, null, '', [], $previous);
        $error->requestNotSent = true;

        return $error;
    }
}
