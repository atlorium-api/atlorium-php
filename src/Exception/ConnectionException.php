<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * Сетевая ошибка: ответа от сервера нет (DNS, TLS, обрыв соединения). Статус — null.
 * GET и DELETE ретраятся автоматически в пределах maxRetries. POST — только если запрос заведомо
 * не дошёл до сервера ({@see isRequestNotSent()}): POST-операции платные, и повтор после обрыва
 * мог бы выполнить операцию второй раз.
 */
class ConnectionException extends AtloriumException
{
    /** Соединение не установилось, и запрос точно не ушёл на сервер. */
    protected bool $requestNotSent = false;

    /**
     * Ошибка, при которой соединение не установилось (DNS, отказ в соединении) и запрос точно не
     * ушёл на сервер. Такую ошибку безопасно повторять для любого метода.
     */
    public static function requestNotSent(string $message, ?\Throwable $previous = null): self
    {
        $error = new self($message, null, '', [], $previous);
        $error->requestNotSent = true;

        return $error;
    }

    /** true — запрос заведомо не дошёл до сервера (соединение не установилось). */
    public function isRequestNotSent(): bool
    {
        return $this->requestNotSent;
    }
}
