<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API ЕГРЮЛ/ЕГРИП: $client->egrul.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/egrul-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class EgrulResource extends AbstractResource
{
    private EgrulRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new EgrulRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): EgrulRawResource
    {
        return $this->raw;
    }

    /**
     * Возвращает карточку организации по ИНН, ОГРН или ОГРНИП.
     *
     * Примеры запросов:
     * GET /api/egrul/7707083893
     * GET /api/egrul/304500116000157?extended=true
     *
     * @param string $query Идентификатор: ИНН (10 цифр у ЮЛ, 12 у ИП), ОГРН (13 цифр) или ОГРНИП (15 цифр). Пример: 7707083893.
     * @param array{extended?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - extended — Расширенные сведения из официальной выписки, которых нет в базовой карточке: у ИП — адрес электронной почты и пол; у ЮЛ — состав участников/учредителей и их доли.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - found (bool) — true, если организация найдена по переданному идентификатору.
     *     - kind ('Unknown'|'LegalEntity'|'SoleProprietor') — Тип субъекта: юридическое лицо (ЕГРЮЛ) или индивидуальный предприниматель (ЕГРИП).
     *     - inn (string|null) — ИНН (10 цифр у ЮЛ, 12 у ИП/физлица).
     *     - ogrn (string|null) — ОГРН (13 цифр у ЮЛ) или ОГРНИП (15 цифр у ИП).
     *     - kpp (string|null) — КПП — только для ЮЛ (9 цифр).
     *     - fullName (string|null) — Полное наименование: для ЮЛ — полное фирменное наименование ("ПУБЛИЧНОЕ АКЦИОНЕРНОЕ ОБЩЕСТВО \"СБЕРБАНК РОССИИ\""), для ИП — ФИО предприним…
     *     - shortName (string|null) — Сокращённое наименование ЮЛ ("ПАО СБЕРБАНК").
     *     - legalFormCode (string|null) — Организационно-правовая форма (ОКОПФ), код.
     *     - status (string|null) — Текстовый статус: "Действующая организация", "Деятельность прекращена" и т.п.
     *     - active (bool) — true, если субъект действующий (не ликвидирован/не прекратил деятельность).
     *     - registrationDate (string|null) — Дата присвоения ОГРН/ОГРНИП (государственной регистрации).
     *     - registrationDateBefore2002 (string|null) — Дата первичной регистрации до 01.07.2002 — только для «старых» ЮЛ, созданных до введения ЕГРЮЛ.
     *     - taxRegistrationDate (string|null) — Дата постановки на учёт в налоговом органе.
     *     - dataDate (string|null) — Дата, на которую актуальны сведения карточки (дата формирования данных).
     *     - address (string|null) — Юридический адрес одной строкой ("117312, Г.МОСКВА, УЛ.
     *     - postalCode (string|null) — Почтовый индекс адреса (если есть).
     *     - regionCode (string|null) — Код региона (субъекта РФ), например "77".
     *     - regionName (string|null) — Наименование региона, например "Г.МОСКВА".
     *     - registrationAuthority (string|null) — Наименование регистрирующего органа (где зарегистрирован субъект).
     *     - registrationAuthorityCode (string|null) — Код регистрирующего органа.
     *     - taxAuthority (string|null) — Наименование налогового органа (где субъект состоит на учёте).
     *     - taxAuthorityCode (string|null) — Код налогового органа.
     *     - primaryOkved (array<string, mixed>|null) — Основной вид деятельности по ОКВЭД (код + наименование).
     *     - primaryOkvedType (string|null) — Тип сведений об основном ОКВЭД: "отчётный" или "заявительный" (как именно эти сведения попали в реестр).
     *     - additionalOkveds (list<array<string, mixed>>) — Дополнительные виды деятельности по ОКВЭД.
     *     - authorizedCapital (float|null) — Размер уставного капитала в рублях.
     *     - capitalType (string|null) — Вид капитала ("УСТАВНЫЙ КАПИТАЛ", "СКЛАДОЧНЫЙ КАПИТАЛ" и т.п.).
     *     - formationMethod (string|null) — Способ образования ЮЛ ("Создание юридического лица до 01.07.2002" и т.п.).
     *     - managers (list<array<string, mixed>>) — Лица, имеющие право действовать без доверенности (руководители).
     *     - founders (list<array<string, mixed>>) — Учредители (участники).
     *     - lastName (string|null) — Фамилия ИП.
     *     - firstName (string|null) — Имя ИП.
     *     - middleName (string|null) — Отчество ИП (при наличии).
     *     - citizenship (string|null) — Гражданство ИП (например, "гражданин Российской Федерации").
     *     - gender (string|null) — Пол ИП ("мужской"/"женский"), если раскрыт в выписке.
     *     - email (string|null) — Адрес электронной почты, если он указан в выписке.
     *     - specialTaxRegimes (list<string>) — Применяемые специальные налоговые режимы (по последним доступным сведениям): например "УСН", "ЕСХН", "ПСН", "НПД", "АУСН".
     *     - smeCategory (string|null) — Категория в реестре малого и среднего предпринимательства (МСП): "Микропредприятие", "Малое предприятие", "Среднее предприятие".
     *     - smeRegistryDate (string|null) — Дата включения в реестр МСП (если применимо).
     *     - publications (list<array<string, mixed>>) — Публикации в «Вестнике государственной регистрации» (для ЮЛ).
     *     - massAddress (bool) — Признак «массового адреса»: по юридическому адресу зарегистрировано много других организаций.
     *     - officialExcerptAvailable (bool) — true, если по субъекту доступна официальная выписка из реестра в PDF (её можно скачать отдельным запросом — см.
     *     - warnings (list<string>) — Предупреждения о качестве данных: например, если какое-то поле не удалось надёжно сопоставить при сборке карточки.
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function get(string $query, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->get($query, $params)->getData();

        return $data;
    }

    /**
     * Ищет субъекты (ЮЛ и ИП) по наименованию или ФИО.
     *
     * Примеры запросов:
     * GET /api/egrul/search?query=Сбербанк
     * GET /api/egrul/search?query=Иванов%20Иван%20Иванович&limit=20
     *
     * @param string $query Строка поиска: часть наименования организации или ФИО ИП. Пример: Сбербанк.
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - limit — Максимум результатов (по умолчанию 10, максимум 50).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - query (string) — Исходный поисковый запрос (как его передал клиент).
     *     - results (list<array<string, mixed>>) — Найденные субъекты (ограничено лимитом запроса).
     *     - count (int) — Сколько результатов вернулось в этом ответе.
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
     * Возвращает официальную выписку из ЕГРЮЛ/ЕГРИП по идентификатору субъекта в виде PDF-файла.
     *
     * Пример запроса:
     * GET /api/egrul/7707083893/excerpt
     *
     * @param string $query ИНН, ОГРН или ОГРНИП субъекта. Пример: 7707083893.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return string содержимое файла
     *
     * @throws AtloriumException
     */
    public function getExcerpt(string $query, array $params = []): string
    {
        /** @var string $data */
        $data = $this->raw->getExcerpt($query, $params)->getData();

        return $data;
    }
}
