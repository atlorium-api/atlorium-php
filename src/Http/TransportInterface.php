<?php

declare(strict_types=1);

namespace Atlorium\Http;

use Atlorium\Exception\ConnectionException;
use Atlorium\Exception\TimeoutException;

/**
 * Транспорт — единственное место, где SDK выходит в сеть.
 *
 * Реализации: {@see CurlTransport} (по умолчанию), {@see Psr18Transport} (свой PSR-18 клиент),
 * {@see MockTransport} (тесты).
 *
 * Транспорт НЕ разбирает статусы: ответ 4xx/5xx — это обычный {@see Response}.
 * Исключение бросается только тогда, когда ответа нет вовсе.
 */
interface TransportInterface
{
    /**
     * Отправляет запрос и возвращает ответ с любым HTTP-статусом.
     *
     * @throws TimeoutException    сервер не ответил за {@see Request::getTimeout()} секунд
     * @throws ConnectionException прочие сетевые ошибки: DNS, TLS, обрыв соединения
     */
    public function send(Request $request): Response;
}
