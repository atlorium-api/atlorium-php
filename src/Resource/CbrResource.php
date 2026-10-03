<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API справочника БИК: $client->cbr.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/cbr-bik-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class CbrResource extends AbstractResource
{
    private CbrRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new CbrRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): CbrRawResource
    {
        return $this->raw;
    }

    /**
     * Возвращает реквизиты участника справочника по точному БИК (9 цифр).
     *
     * Пример запроса:
     * GET /api/cbr/044525225
     *
     * @param string $bik БИК — банковский идентификационный код (ровно 9 цифр). Пример: 044525225 (ПАО Сбербанк).
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - bik (string) — БИК — банковский идентификационный код (9 цифр).
     *     - name (string) — Наименование участника (ParticipantInfo@NameP).
     *     - englishName (string|null) — Наименование участника на английском (ParticipantInfo@EnglName).
     *     - corrAccount (string|null) — Корреспондентский счёт участника (счёт из <Accounts> с типом RegulationAccountType="CRSA").
     *     - countryCode (string|null) — Код страны участника по ISO-3166 alpha-2 (ParticipantInfo@CntrCd, обычно "RU").
     *     - parentBic (string|null) — БИК головной организации (ParticipantInfo@PrntBIC).
     *     - regionCode (string|null) — Код региона РФ (ParticipantInfo@Rgn, 2 цифры).
     *     - postalIndex (string|null) — Почтовый индекс (ParticipantInfo@Ind).
     *     - localityType (string|null) — Тип населённого пункта (ParticipantInfo@Tnp, например "г", "пгт").
     *     - locality (string|null) — Наименование населённого пункта (ParticipantInfo@Nnp).
     *     - address (string|null) — Адрес участника (ParticipantInfo@Adr).
     *     - registrationNumber (string|null) — Регистрационный номер кредитной организации по КГРКО (ParticipantInfo@RegN).
     *     - uid (string|null) — Уникальный идентификатор участника в справочнике (ParticipantInfo@UID).
     *     - dateIn (string|null) — Дата включения участника в справочник (ParticipantInfo@DateIn).
     *     - swift (string|null) — «Основной» SWIFT/BIC-код участника (помеченный DefaultSWBIC="1", иначе первый из списка) — Полный список кодов — в swiftCodes.
     *     - swiftCodes (list<string>) — Все SWIFT/BIC-коды участника (атрибуты SWBIC всех элементов <SWBICS>).
     *     - accounts (list<array<string, mixed>>) — Все счета участника из блока <Accounts> с их реквизитами (номер, контрольный ключ, подразделение Банка России, статус, дата открытия).
     *     - restrictions (list<array<string, mixed>>) — Ограничения, наложенные на участника (блок <RstrList>).
     *     - participantType (string|null) — Тип участника (ParticipantInfo@PtType).
     *     - serviceCode (string|null) — Код участия в системах/услугах Банка России (ParticipantInfo@Srvcs).
     *     - exchangeType (string|null) — Тип обмена электронными сообщениями с Банком России (ParticipantInfo@XchType).
     *     - status (string|null) — Статус участника (ParticipantInfo@ParticipantStatus): "PSAC" — действующий.
     *
     * @throws AtloriumException
     */
    public function get(string $bik, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->get($bik, $params)->getData();

        return $data;
    }

    /**
     * Ищет участников по наименованию, городу, SWIFT или началу БИК.
     *
     * Примеры запросов:
     * GET /api/cbr/search?query=Сбербанк
     * GET /api/cbr/search?query=0445&limit=50
     *
     * @param string $query Строка поиска. Если состоит только из цифр — трактуется как БИК (поиск по началу кода), иначе ищется как подстрока в наименовании, населённом пункте и SWIFT.
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - limit — Максимальное количество результатов (по умолчанию 20, максимум 100).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - query (string) — Исходная строка запроса (как её ввёл пользователь).
     *     - results (list<array<string, mixed>>) — Найденные участники справочника.
     *     - count (int) — Количество элементов в results.
     *     - elapsedMs (int) — Время выполнения поиска в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function search(string $query, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->search($query, $params)->getData();

        return $data;
    }

    /**
     * Возвращает статистику загруженного справочника: размер, дата актуальности, готовность.
     *
     * Пример запроса:
     * GET /api/cbr/stats
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - isReady (bool) — Готов ли справочник отвечать на запросы.
     *     - totalEntries (int) — Всего записей в справочнике.
     *     - withCorrAccount (int) — Количество участников, имеющих корреспондентский счёт (CRSA).
     *     - withSwift (int) — Количество участников с заполненным SWIFT.
     *     - directoryDate (string|null) — Дата, на которую актуален справочник (атрибут BusinessDay корня <ED807>).
     *     - loadedAtUtc (string|null) — Момент (UTC) последнего обновления данных справочника.
     *
     * @throws AtloriumException
     */
    public function getStats(array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->getStats($params)->getData();

        return $data;
    }
}
