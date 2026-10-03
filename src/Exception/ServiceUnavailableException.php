<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * HTTP 503: сервис временно недоступен — сбой внешнего источника данных или плановые работы
 * (коды ошибки service_disabled, maintenance; состояние — https://atlorium.com/status).
 * Деньги за такой ответ не списываются.
 *
 * Наследник {@see ServerException}: обработчик любых ошибок 5xx ловит и его. Сбой источника SDK
 * повторяет автоматически (исключение — попытки исчерпаны); плановые работы
 * (service_disabled, maintenance) не повторяются — повтор через секунду бесполезен.
 */
class ServiceUnavailableException extends ServerException
{
    /** true, если сервис выключен на плановые работы (коды service_disabled, maintenance). */
    public function isMaintenance(): bool
    {
        return in_array($this->getErrorCode(), ['service_disabled', 'maintenance'], true);
    }
}
