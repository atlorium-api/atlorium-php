<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API валидации телефона: $client->phone.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/phone-validation-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class PhoneResource extends AbstractResource
{
    private PhoneRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new PhoneRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): PhoneRawResource
    {
        return $this->raw;
    }

    /**
     * Возвращает карточку телефонного номера: валидность, форматы записи, страну, тип номера,
     * оператора диапазона, регион и часовые пояса.
     *
     * Примеры запросов:
     * GET /api/phone/%2B79161234567
     * GET /api/phone/89161234567?countryCode=RU
     *
     * ЧТО ЗНАЧИТ isValid. Номер записан правильно и попадает в диапазон, реально выделенный
     * в его стране. Это НЕ означает, что номер кому-то принадлежит и что он доступен.
     *
     * ЧТО ЗНАЧИТ originalCarrier. Оператор, которому ВЫДЕЛЕН диапазон номера. Абонент вправе
     * перенести номер к другому оператору, сохранив его (MNP), и справочник нумерации об этом
     * не знает. Для произвольного номера это поле — обоснованная догадка, а не факт.
     *
     * @param string $number Номер телефона. Принимается в международном формате ("+79161234567") или в национальном ("8 916 123-45-67") — во втором случае обязательно укажите countryCode. Пробелы, скобки и дефисы допустимы. Знак «+» в URL кодируется как %2B.
     * @param array{countryCode?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - countryCode — Код страны (ISO-3166 alpha-2, например "RU"), в которой трактовать номер, записанный в национальном формате.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - input (string) — Номер в том виде, в каком он был передан в запросе.
     *     - number (array<string, mixed>) — Результат офлайн-разбора номера.
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function validate(string $number, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->validate($number, $params)->getData();

        return $data;
    }
}
