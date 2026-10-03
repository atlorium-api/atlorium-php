<?php

declare(strict_types=1);

namespace Atlorium\Exception;

/**
 * HTTP 403: сервис не входит в условия вашей учётной записи (код ошибки
 * service_not_in_account_terms). Не ретраится. Чтобы подключить сервис, напишите на
 * support@atlorium.com.
 */
class PermissionDeniedException extends AtloriumException
{
}
