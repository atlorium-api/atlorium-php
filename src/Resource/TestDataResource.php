<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API генератора тестовых данных РФ: $client->testdata.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/test-data-generator-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class TestDataResource extends AbstractResource
{
    private TestDataRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new TestDataRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): TestDataRawResource
    {
        return $this->raw;
    }

    /**
     * Генерирует набор тестовых записей. Параметры передаются в строке запроса — самый
     * быстрый способ получить данные из curl, браузера или Swagger.
     *
     * Пример запроса:
     * GET /api/testdata?count=5&fields=fullName,innPerson,snils&seed=42
     *
     * ТАРИФИКАЦИЯ. Списывается по одной единице за каждую сгенерированную запись.
     * Отклонённый запрос (400) не тарифицируется: резерв снимается без списания.
     *
     * @param array{count?: int, fields?: string|list<string>, seed?: int, gender?: 'any'|'male'|'female', format?: 'json'|'csv', timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - count — Сколько записей сгенерировать: от 1 до 100.
     *     - fields — Список полей через запятую, например fullName,innPerson,snils.
     *     - seed — Начальное значение генератора.
     *     - gender — Пол генерируемых персон: any (по умолчанию, выбирается случайно по каждой записи), male или female.
     *     - format — Формат ответа: json (по умолчанию) или csv.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>|string при format = 'csv' — строка CSV, иначе массив
     *     Поля ответа:
     *     - seed (int) — Начальное значение генератора, фактически использованное при построении выдачи.
     *     - count (int) — Количество записей в records.
     *     - fields (list<string>) — Идентификаторы полей в порядке следования — он же порядок колонок в CSV.
     *     - records (list<array<string, mixed>>) — Сгенерированные записи.
     *     - disclaimer (string) — Напоминание о характере данных, которое едет вместе с каждым ответом.
     *
     * @throws AtloriumException
     */
    public function generate(array $params = [])
    {
        /** @var array<string, mixed>|string $data */
        $data = $this->raw->generate($params)->getData();

        return $data;
    }

    /**
     * Генерирует набор тестовых записей по параметрам из тела запроса. Отличается от
     * GET-варианта только способом передачи параметров — контракт и цена те же.
     *
     * Пример запроса:
     * POST /api/testdata
     * {
     *   "count": 5,
     *   "seed": 42,
     *   "fields": ["fullName", "innPerson", "snils"],
     *   "gender": "female",
     *   "format": "json"
     * }
     *
     * Тело удобнее строки запроса, когда полей много: список приходит массивом, а не
     * строкой через запятую, и не упирается в предел длины URL.
     *
     * ТАРИФИКАЦИЯ. Списывается по одной единице за каждую сгенерированную запись.
     *
     * @param array{count?: int, seed?: int, fields?: string|list<string>, gender?: 'any'|'male'|'female', format?: 'json'|'csv', timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - count — Сколько записей сгенерировать.
     *     - seed — Начальное значение генератора случайных чисел.
     *     - fields — Список полей, которые нужно сгенерировать (идентификаторы из каталога TestDataFields).
     *     - gender — Пол генерируемых персон: any (по умолчанию), male или female.
     *     - format — Формат ответа: json (по умолчанию) или csv.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>|string при format = 'csv' — строка CSV, иначе массив
     *     Поля ответа:
     *     - seed (int) — Начальное значение генератора, фактически использованное при построении выдачи.
     *     - count (int) — Количество записей в records.
     *     - fields (list<string>) — Идентификаторы полей в порядке следования — он же порядок колонок в CSV.
     *     - records (list<array<string, mixed>>) — Сгенерированные записи.
     *     - disclaimer (string) — Напоминание о характере данных, которое едет вместе с каждым ответом.
     *
     * @throws AtloriumException
     */
    public function generateWithBody(array $params = [])
    {
        /** @var array<string, mixed>|string $data */
        $data = $this->raw->generateWithBody($params)->getData();

        return $data;
    }

    /**
     * Возвращает справочник всех типов данных, которые умеет генерировать сервис:
     * идентификатор поля, название, пояснение, смысловую группу, пример значения и признак
     * наличия контрольной суммы.
     *
     * Пример запроса:
     * GET /api/testdata/fields
     *
     * Идентификаторы из этого справочника передаются в параметре fields запроса
     * генерации. Справочник — часть контракта: если поле есть здесь, его можно запросить.
     *
     * ТАРИФИКАЦИЯ. Запрос БЕСПЛАТНЫЙ: справочник описывает контракт сервиса, а не является
     * результатом его работы. Ограничение частоты запросов на него действует общее.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return list<array<string, mixed>>
     *     Поля ответа (каждого элемента):
     *     - id (string) — Технический идентификатор поля — то, что клиент передаёт в запросе и что становится ключом в JSON-записи и заголовком колонки CSV.
     *     - title (string) — Название поля по-русски.
     *     - titleEn (string) — Название поля по-английски.
     *     - description (string) — Пояснение: что именно в поле и как оно устроено.
     *     - descriptionEn (string) — То же по-английски.
     *     - group (string) — Смысловая группа поля, по которой поля удобно группировать при выборе: «Персона», «Налоговые реквизиты», «Банк», «Документы», «Контакты», «…
     *     - hasChecksum (bool) — true, если у значения есть рассчитываемый контрольный разряд (ИНН, СНИЛС, ОГРН, счёт, номер карты, VIN) — такое значение пройдёт проверку к…
     *     - sample (string) — Пример значения этого поля.
     *
     * @throws AtloriumException
     */
    public function listFields(array $params = []): array
    {
        /** @var list<array<string, mixed>> $data */
        $data = $this->raw->listFields($params)->getData();

        return $data;
    }
}
