<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * ГАР/ФИАС API: $client->gar.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/gar-fias-address-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class GarResource extends AbstractResource
{
    private GarRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new GarRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): GarRawResource
    {
        return $this->raw;
    }

    /**
     * Ищет адресные объекты по произвольной строке запроса: разбирает её на части и
     * находит регионы, населённые пункты, улицы, дома, квартиры и комнаты.
     *
     * Пример запроса:
     * GET /api/gar/search?query=Москва ул Ленина д 5
     * GET /api/gar/search?query=Самарская, Самара, Пушкина&limit=20
     *
     * @param string $query Строка поиска адреса. Формат: произвольный текст, элементы можно разделять запятыми. Примеры: - Москва ул Ленина д 5 - Самарская обл, Самара, Ленина, д 5 кв 18 - ул Пушкина
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - limit — Максимальное количество результатов (по умолчанию 10, максимум 100).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - query (string) — Исходная строка запроса.
     *     - results (list<array<string, mixed>>) — Список найденных адресных объектов.
     *     - count (int) — Количество найденных результатов.
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
     * Возвращает подсказки автодополнения для быстрого поиска адресных объектов.
     * Облегчённая версия search — возвращает только имена и идентификаторы.
     *
     * @param string $query Строка для автодополнения (минимум 2 символа).
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - limit — Максимальное количество подсказок (по умолчанию 7, максимум 20).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - query (string) — Исходный запрос.
     *     - items (list<array<string, mixed>>) — Список подсказок.
     *     - count (int) — Количество подсказок.
     *     - elapsedMs (int) — Время выполнения в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function suggest(string $query, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->suggest($query, $params)->getData();

        return $data;
    }

    /**
     * Получает детальную информацию об объекте ГАР по его UUID (GUID).
     * Включает иерархию, параметры (почтовый индекс, ОКТМО, ОКАТО, ИФНС, КЛАДР).
     *
     * @param string $guid UUID (GUID) объекта ГАР. Формат: стандартный GUID (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - objectId (int) — Числовой идентификатор объекта.
     *     - objectGuid (string) — UUID (GUID) объекта.
     *     - fullAddress (string) — Полный адрес объекта (из иерархии).
     *     - objectType (string) — Тип объекта.
     *     - regionCode (string) — Код региона (2 цифры).
     *     - hierarchy (list<array<string, mixed>>) — Иерархия: путь от корня (региона) к данному объекту.
     *     - parameters (list<array<string, mixed>>) — Параметры объекта (индекс, ОКТМО, ОКАТО, ИФНС, КЛАДР и т.д.).
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function getObject(string $guid, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->getObject($guid, $params)->getData();

        return $data;
    }

    /**
     * Получает детальную информацию об объекте по числовому идентификатору (object_id).
     *
     * @param int|string $objectId Числовой идентификатор объекта ГАР.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - objectId (int) — Числовой идентификатор объекта.
     *     - objectGuid (string) — UUID (GUID) объекта.
     *     - fullAddress (string) — Полный адрес объекта (из иерархии).
     *     - objectType (string) — Тип объекта.
     *     - regionCode (string) — Код региона (2 цифры).
     *     - hierarchy (list<array<string, mixed>>) — Иерархия: путь от корня (региона) к данному объекту.
     *     - parameters (list<array<string, mixed>>) — Параметры объекта (индекс, ОКТМО, ОКАТО, ИФНС, КЛАДР и т.д.).
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function getObjectById($objectId, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->getObjectById($objectId, $params)->getData();

        return $data;
    }

    /**
     * Получает список дочерних объектов для указанного родителя.
     * Например: дома на улице, квартиры в доме, комнаты в квартире.
     *
     * @param int|string $parentObjectId Идентификатор родительского объекта.
     * @param array{limit?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - limit — Максимальное количество результатов (по умолчанию 50, максимум 500).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - parentObjectId (int) — Идентификатор родительского объекта.
     *     - children (list<array<string, mixed>>) — Список дочерних объектов.
     *     - count (int) — Общее количество дочерних объектов.
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function getChildren($parentObjectId, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->getChildren($parentObjectId, $params)->getData();

        return $data;
    }

    /**
     * Получает иерархию объекта — путь от корня (региона) до указанного объекта.
     *
     * @param int|string $objectId Идентификатор объекта.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - objectId (int) — Идентификатор запрошенного объекта.
     *     - path (list<array<string, mixed>>) — Путь в иерархии от корня (региона) к объекту.
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function getHierarchy($objectId, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->getHierarchy($objectId, $params)->getData();

        return $data;
    }

    /**
     * Возвращает статистику загруженных данных ГАР:
     * количество объектов по типам, загруженные регионы и состояние готовности.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - addressObjectCount (int) — Количество адресных объектов.
     *     - houseCount (int) — Количество домов.
     *     - apartmentCount (int) — Количество квартир и помещений.
     *     - roomCount (int) — Количество комнат.
     *     - steadCount (int) — Количество земельных участков.
     *     - carplaceCount (int) — Количество машино-мест.
     *     - nonResidentialCount (int) — Количество нежилых помещений: офисы, склады, цеха, торговые залы, павильоны, подвалы, котельные, погреба, рабочие участки и помещения без у…
     *     - garageCount (int) — Количество гаражей.
     *     - unknownTypeCount (int) — Количество объектов, тип которых в реестре не указан.
     *     - breakdown (array<string, mixed>) — Полная разбивка по каноническим типам объектов: «квартира», «комната», «помещение», «гараж» и так далее, как их отдаёт геокодер.
     *     - totalObjectCount (int) — Общее количество объектов реестра всех типов.
     *     - loadedRegions (list<string>) — Список загруженных регионов.
     *     - isReady (bool) — Признак: данные загружены и готовы к запросам.
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function getStats(array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->getStats($params)->getData();

        return $data;
    }

    /**
     * Возвращает список доступных регионов (субъектов РФ) из загруженных данных ГАР.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return list<array<string, mixed>>
     *     Поля ответа (каждого элемента):
     *     - code (string) — Код региона (2 цифры, например "77").
     *     - name (string) — Наименование региона (например, "Москва", "Самарская обл").
     *     - objectId (int) — Числовой идентификатор объекта региона в ГАР.
     *
     * @throws AtloriumException
     */
    public function listRegions(array $params = []): array
    {
        /** @var list<array<string, mixed>> $data */
        $data = $this->raw->listRegions($params)->getData();

        return $data;
    }

    /**
     * Возвращает статистику по конкретному региону: количество объектов по типам.
     *
     * @param string $regionCode Код региона (2 цифры, например "77" = Москва).
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - regionCode (string) — Код региона (2 цифры).
     *     - regionName (string) — Наименование региона.
     *     - addressObjectCount (int) — Количество адресных объектов в регионе.
     *     - houseCount (int) — Количество домов в регионе.
     *     - apartmentCount (int) — Количество квартир/помещений в регионе.
     *     - roomCount (int) — Количество комнат в регионе.
     *     - steadCount (int) — Количество земельных участков в регионе.
     *     - carplaceCount (int) — Количество машино-мест в регионе.
     *     - nonResidentialCount (int) — Количество нежилых помещений: офисы, склады, цеха, торговые залы, павильоны, подвалы, котельные, погреба, рабочие участки и помещения без у…
     *     - garageCount (int) — Количество гаражей.
     *     - unknownTypeCount (int) — Количество объектов, тип которых в реестре не указан.
     *     - breakdown (array<string, mixed>) — Полная разбивка по каноническим типам объектов: «квартира», «комната», «помещение», «гараж» и так далее, как их отдаёт геокодер.
     *     - totalObjectCount (int) — Общее количество объектов реестра всех типов в регионе.
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function getRegionStats(string $regionCode, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->getRegionStats($regionCode, $params)->getData();

        return $data;
    }
}
