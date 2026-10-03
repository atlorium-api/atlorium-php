<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * HTTP 402: недостаточно кредитов на балансе (или аккаунт не активирован). Не ретраится.
 */
class InsufficientCreditsException extends AtloriumException
{
    /** Текущий баланс в кредитах, если сервер его прислал (поле balance). */
    public function getBalance(): ?float
    {
        return $this->floatField('balance');
    }
}
