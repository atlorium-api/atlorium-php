<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API справочника SWIFT/BIC: $client->swift.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/swift-bic-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class SwiftResource extends AbstractResource
{
    private SwiftRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new SwiftRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): SwiftRawResource
    {
        return $this->raw;
    }

    /**
     * Возвращает карточку организации по точному SWIFT/BIC (8 или 11 символов).
     *
     * Пример запроса:
     * GET /api/swift/DEUTDEFF
     *
     * @param string $bic SWIFT/BIC-код. 8-значный (головной офис) дополняется до 11 ("XXX"). Пример: DEUTDEFF.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - bic (string|null) — Основной SWIFT/BIC-код организации (8 или 11 символов).
     *     - lei (string) — LEI — Legal Entity Identifier, 20-значный международный идентификатор юридического лица (стандарт ISO 17442).
     *     - name (string) — Юридическое наименование организации.
     *     - otherNames (list<string>) — Прочие наименования организации: торговые марки, сокращённые названия, написания на других языках/в другой транслитерации.
     *     - country (string|null) — Код страны юридического адреса в формате ISO-3166 alpha-2 (например, "DE", "RU").
     *     - jurisdiction (string|null) — Код юрисдикции регистрации организации (ISO-3166, например "DE" или субкод вида "US-DE").
     *     - entityCategory (string|null) — Категория организации: например "GENERAL" — обычное юр.
     *     - entitySubCategory (string|null) — Подкатегория организации (поле subCategory): уточняет категорию для отдельных типов (например, виды фондов).
     *     - legalForm (string|null) — Код организационно-правовой формы (ELF — Entity Legal Form, стандарт ISO 20275; например "6QQB" для немецкой AG).
     *     - city (string|null) — Город юридического адреса.
     *     - region (string|null) — Регион/субъект юридического адреса (например, "DE-HE" — Гессен).
     *     - postalCode (string|null) — Почтовый индекс юридического адреса.
     *     - address (string|null) — Юридический адрес одной строкой — все содержательные строки адреса (улица, дом, корпус), объединённые через запятую.
     *     - headquarters (array<string, mixed>|null) — Адрес штаб-квартиры (фактического головного офиса).
     *     - registrationAuthorityId (string|null) — Идентификатор регистрирующего органа, в котором организация внесена в реестр (код вида "RA000242" — например, для Германии это торговый рее…
     *     - registrationId (string|null) — Регистрационный номер организации в этом органе (например, "HRB 30000" — номер в немецком торговом реестре).
     *     - entityCreationDate (string|null) — Дата создания (учреждения) юридического лица (UTC).
     *     - entityExpirationDate (string|null) — Дата прекращения существования организации (UTC).
     *     - entityExpirationReason (string|null) — Причина прекращения существования (поле expiration.reason): например "DISSOLVED" — ликвидирована, "CORPORATE_ACTION" — реорганизация.
     *     - associatedEntityLei (string|null) — LEI ассоциированной организации (поле associatedEntity.lei): для фондов — управляющая компания и т.п.
     *     - associatedEntityName (string|null) — Наименование ассоциированной организации (associatedEntity.name).
     *     - entityStatus (string|null) — Статус юридического лица: "ACTIVE" — действует, "INACTIVE" — ликвидировано/присоединено.
     *     - registrationStatus (string|null) — Статус актуальности регистрационной записи: "ISSUED" — актуальна и подтверждена, "LAPSED" — не продлевалась (данные могли устареть), "RETIR…
     *     - lastUpdate (string|null) — Дата последнего обновления регистрационной записи (UTC).
     *     - initialRegistrationDate (string|null) — Дата первичной регистрации записи об организации в реестре (UTC).
     *     - nextRenewalDate (string|null) — Дата, до которой запись подтверждена и подлежит следующему продлению (UTC).
     *     - managingLou (string|null) — Идентификатор организации, которая ведёт (поддерживает) эту регистрационную запись — 20-значный код по тому же стандарту, что и LEI.
     *     - corroborationLevel (string|null) — Уровень подтверждённости данных записи: "FULLY_CORROBORATED" — полностью сверены с официальным реестром, "PARTIALLY_CORROBORATED"/"ENTITY_S…
     *     - validationAuthorityId (string|null) — Идентификатор органа, по данным которого ПОДТВЕРЖДАЛАСЬ запись (validatedAt.id).
     *     - validationId (string|null) — Номер организации в органе подтверждения (validatedAs).
     *     - conformityFlag (string|null) — Признак соответствия записи политике качества данных (conformityFlag): "CONFORMING" — соответствует, "NON_CONFORMING" — нет.
     *     - qualityCode (string|null) — Служебный код качества регистрационной записи.
     *     - legalEvents (list<array<string, mixed>>) — История юридических событий организации (слияния, смена адреса/наименования и т.п.) из блока eventGroups.
     *     - mailRouting (string|null) — Готовая почтовая строка юридического адреса (поле mailRouting): индекс, город и адрес одной строкой, как принято в стране организации.
     *     - otherAddresses (list<array<string, mixed>>) — Альтернативные адреса организации — прежде всего адрес на другом языке (например, английская версия юр.
     *     - successorLei (string|null) — LEI организации-правопреемника.
     *     - successorName (string|null) — Наименование организации-правопреемника (если есть).
     *     - mic (string|null) — MIC — Market Identifier Code (ISO 10383), код торговой площадки.
     *     - openCorporatesId (string|null) — Идентификатор организации в реестре OpenCorporates (внешняя сверка контрагентов).
     *     - spGlobalIds (list<string>) — Идентификаторы организации в данных S&P Global (внешняя сверка/обогащение).
     *     - allBics (list<string>) — Все SWIFT/BIC-коды, привязанные к этой организации.
     *
     * @throws AtloriumException
     */
    public function get(string $bic, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->get($bic, $params)->getData();

        return $data;
    }

    /**
     * Ищет организации по наименованию (полнотекстовый поиск).
     *
     * Примеры запросов:
     * GET /api/swift/search?query=Sberbank
     * GET /api/swift/search?query=Deutsche%20Bank&limit=50
     *
     * @param string $query Строка поиска — часть наименования организации. Пример: Sberbank.
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - limit — Максимум результатов (по умолчанию 20, максимум 100).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - query (string) — Исходная строка запроса — возвращается без изменений.
     *     - results (list<array<string, mixed>>) — Найденные организации (текущая страница ответа, не больше запрошенного limit).
     *     - count (int) — Количество элементов в results (на текущей странице).
     *     - totalMatches (int) — Сколько всего организаций соответствует запросу.
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
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
     * Проверяет формат SWIFT/BIC по стандарту ISO-9362 и разбирает его на части, мгновенно.
     * Проверяет только формат, а не существование такого кода.
     *
     * Пример запроса:
     * GET /api/swift/validate/DEUTDEFFXXX
     *
     * @param string $bic Проверяемый BIC (8 или 11 символов).
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - normalizedBic (string) — Нормализованный код (верхний регистр, без пробелов; 8-значный дополнен "XXX").
     *     - isValidFormat (bool) — true, если строка соответствует формату ISO-9362 (длина и наборы символов по позициям).
     *     - message (string) — Понятное человеку пояснение результата (что именно не так с форматом, либо «формат корректен»).
     *     - bankCode (string|null) — Код организации — первые 4 буквы (позиции 1–4, institution code по ISO-9362).
     *     - countryCode (string|null) — Код страны — позиции 5–6, буквы ISO-3166 alpha-2.
     *     - locationCode (string|null) — Код местоположения — позиции 7–8 (буквы или цифры).
     *     - branchCode (string|null) — Код филиала — позиции 9–11 (опционально).
     *     - isPrimaryOffice (bool) — true, если код относится к головному офису (филиал отсутствует или равен "XXX").
     *
     * @throws AtloriumException
     */
    public function validate(string $bic, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->validate($bic, $params)->getData();

        return $data;
    }

    /**
     * Возвращает служебную информацию сервиса: готовность отвечать на запросы.
     *
     * Пример запроса:
     * GET /api/swift/stats
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - isReady (bool) — Готов ли сервис отвечать на запросы (для справочника SWIFT/BIC — практически всегда true).
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
