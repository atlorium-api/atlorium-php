<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API проверки почты: $client->email.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/email-verification-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class EmailResource extends AbstractResource
{
    private EmailRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new EmailRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): EmailRawResource
    {
        return $this->raw;
    }

    /**
     * Проверяет почтовый адрес: существует ли ящик, принимает ли домен почту, не одноразовый
     * ли это ящик, не спам-ловушка и не ролевой ли адрес.
     *
     * Пример запроса:
     * GET /api/emailvalidation?email=user@example.com
     *
     * ТАРИФИКАЦИЯ. Запрос не тарифицируется, если адрес синтаксически некорректен (400) либо
     * если проверить адрес не удалось и вернулся статус unknown (почтовый сервер домена
     * применил greylisting, не ответил или заблокировал проверку). Платите только за
     * содержательный вердикт.
     *
     * @param string $email Почтовый адрес для проверки. Пример: user@example.com
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - email (string) — Проверенный адрес в нормализованном виде (обрезанные пробелы, нижний регистр домена).
     *     - status ('Valid'|'Invalid'|'CatchAll'|'Unknown'|'SpamTrap'|'Abuse'|'DoNotMail') — Итоговый вердикт.
     *     - statusRaw (string) — Вердикт в исходном строковом виде ("valid", "catch-all", "do_not_mail", ...).
     *     - subStatus (string|null) — Уточнение вердикта: "mailbox_not_found", "disposable", "role_based", "greylisted" и т.п.
     *     - account (string|null) — Часть адреса ДО «@» (локальная часть).
     *     - domain (string|null) — Часть адреса ПОСЛЕ «@» (домен).
     *     - freeEmail (bool) — true — адрес на бесплатном публичном почтовике (gmail.com, mail.ru, yandex.ru...).
     *     - catchAllDomain (bool|null) — true — домен настроен как catch-all (отвечает «валидно» на любой ящик), false — нет, null — определить не удалось.
     *     - didYouMean (string|null) — Предполагаемое исправление опечатки в домене ("gmial.com" → "user@gmail.com").
     *     - domainAgeDays (int|null) — Возраст домена в днях.
     *     - smtpProvider (string|null) — Почтовая платформа домена ("g-suite", "outlook", ...).
     *     - mxFound (bool) — true — у домена есть MX-запись (то есть он в принципе может принимать почту).
     *     - mxRecord (string|null) — Приоритетная MX-запись домена ("aspmx.l.google.com").
     *     - activeInDays (string|null) — Давность последней активности адреса ("30", "60", ..., "365+").
     *     - activeFirstSeen (string|null) — Дата первого зафиксированного сигнала активности.
     *     - processedAtUtc (string|null) — Момент, когда адрес был проверен (UTC).
     *     - elapsedMs (int) — Длительность обработки запроса в миллисекундах.
     *     - deliverable (bool) — Можно ли безопасно писать на адрес.
     *
     * @throws AtloriumException
     */
    public function validate(string $email, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->validate($email, $params)->getData();

        return $data;
    }
}
