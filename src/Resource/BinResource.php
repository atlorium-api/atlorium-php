<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API BIN Lookup: $client->bin.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/bin-lookup-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class BinResource extends AbstractResource
{
    private BinRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new BinRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): BinRawResource
    {
        return $this->raw;
    }

    /**
     * Возвращает карточку по BIN/IIN (6, 8 или 10 цифр). Для максимальной точности
     * рекомендуется передавать 8 и более цифр.
     *
     * Примеры запросов:
     * GET /api/bin/48334884
     * GET /api/bin/48334884?customerIp=8.8.8.8
     *
     * @param string $bin BIN/IIN — первые 6, 8 или 10 цифр номера карты. Пример: 48334884.
     * @param array{customerIp?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - customerIp — (Необязательно) IP-адрес клиента.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - valid (bool) — true, если переданный номер является валидным BIN/IIN и найден в справочнике.
     *     - binNumber (string) — Сам BIN/IIN, по которому выполнялся поиск (только цифры, 6/8/10 знаков).
     *     - binLength (int) — Длина BIN в цифрах (6, 8 или 10).
     *     - cardBrand (string|null) — Платёжная система (бренд) карты: например "VISA", "MASTERCARD", "AMERICAN EXPRESS", "MAESTRO", "UNIONPAY", "JCB".
     *     - cardType (string|null) — Тип карты по способу списания средств.
     *     - cardCategory (string|null) — Категория (продукт) карты.
     *     - country (string|null) — Полное название страны банка-эмитента (например, "NEW ZEALAND").
     *     - countryCode (string|null) — Код страны эмитента в формате ISO-3166 alpha-2 (например, "NZ").
     *     - countryCode3 (string|null) — Код страны эмитента в формате ISO-3166 alpha-3 (например, "NZL").
     *     - currencyCode (string|null) — Код валюты страны эмитента в формате ISO-4217 (например, "NZD", "USD", "EUR").
     *     - issuer (string|null) — Название банка-эмитента карты (например, "KIWIBANK, LTD.").
     *     - issuerWebsite (string|null) — Сайт банка-эмитента (например, "http://www.kiwibank.co.nz").
     *     - issuerPhone (string|null) — Контактный телефон банка-эмитента.
     *     - isCommercial (bool) — true, если это коммерческая (бизнес/корпоративная) карта.
     *     - isPrepaid (bool) — true, если это предоплаченная (prepaid) карта.
     *     - isReloadable (bool) — true, если карту можно пополнять (reloadable).
     *     - hasCustomerIp (bool) — Был ли в запросе передан IP клиента (и, соответственно, заполнен ли антифрод-блок ниже).
     *     - ipMatchesBin (bool) — true, если страна IP-адреса клиента совпадает со страной банка-эмитента карты.
     *     - ipCountry (string|null) — Страна IP-адреса клиента (если IP был передан и определён).
     *     - ipCountryCode (string|null) — Код страны IP клиента в формате ISO-3166 alpha-2.
     *     - ipCountryCode3 (string|null) — Код страны IP клиента в формате ISO-3166 alpha-3.
     *     - ipRegion (string|null) — Регион IP клиента (если удалось определить).
     *     - ipCity (string|null) — Город IP клиента (если удалось определить).
     *     - ipBlocklisted (bool) — true, если IP клиента числится в одном из списков блокировок (известные источники мошенничества, спама, бот-сетей, прокси и т.п.).
     *     - ipBlocklists (list<string>) — Список конкретных списков блокировок, в которых найден IP клиента (если найден).
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function lookup(string $bin, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->lookup($bin, $params)->getData();

        return $data;
    }

    /**
     * Мгновенно проверяет формат BIN/IIN (только цифры, длина 6, 8 или 10).
     * Проверяет формат, а не существование такого BIN.
     *
     * Пример запроса:
     * GET /api/bin/validate/48334884
     *
     * @param string $bin Проверяемый BIN (6, 8 или 10 цифр; пробелы/разделители игнорируются).
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - normalizedBin (string) — Нормализованный BIN: убраны пробелы и любые разделители, оставлены только цифры.
     *     - isValidFormat (bool) — true, если строка после нормализации состоит только из цифр и имеет допустимую длину (6, 8 или 10 знаков).
     *     - binLength (int) — Длина нормализованного BIN в цифрах (для отображения и проверок разрядности).
     *     - message (string) — Понятное человеку пояснение результата (что не так с форматом, либо «формат корректен»).
     *
     * @throws AtloriumException
     */
    public function validate(string $bin, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->validate($bin, $params)->getData();

        return $data;
    }
}
