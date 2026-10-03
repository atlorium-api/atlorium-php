<?php

declare(strict_types=1);

namespace Atlorium\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;

/** Сетевая ошибка PSR-18 клиента для тестов. */
final class FakeClientException extends \RuntimeException implements ClientExceptionInterface
{
}
