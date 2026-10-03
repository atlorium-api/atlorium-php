<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API обратного геокодинга: $client->geocodereverse.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/reverse-geocoding-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class GeocodeReverseResource extends AbstractResource
{
    private GeocodeReverseRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new GeocodeReverseRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): GeocodeReverseRawResource
    {
        return $this->raw;
    }

    /**
     * Находит ближайший адрес по координатам и добавляет запрошенные обогащения.
     *
     * Пример простого запроса (одна единица работы):
     * GET /api/geocodereverse?lat=55.788226&lon=49.121984
     *
     * Пример с обогащениями (до четырёх единиц работы):
     * GET /api/geocodereverse?lat=55.788226&lon=49.121984&includeNeighbors=true&includeAdmin=true&includeNearby=true
     *
     * ЗАЧЕМ СОСЕДНИЕ ДОМА. Ответом на вопрос «какой здесь адрес» является ОДИН объект —
     * ближайший. Но точка нередко стоит между двумя зданиями или у торца длинного дома, и
     * автоматический выбор в этом случае спорный. Соседи дают возможность разрешить сомнение
     * вручную, не делая второй запрос с другим радиусом.
     *
     * ЗАЧЕМ ПРИНАДЛЕЖНОСТЬ ПО ГРАНИЦАМ. Она считается попаданием точки в полигон, а не
     * выводится из найденного дома. Разница принципиальна: иначе точка в чистом поле
     * унаследовала бы район ближайшей деревни за двадцать километров.
     *
     * ЧТО ВЕРНЁТСЯ ПРИ ПУСТОМ ОБОГАЩЕНИИ. Поле просто не заполняется, а единица работы за него
     * не списывается: запрос остаётся успешным, потому что базовый ответ — адрес — получен.
     *
     * ТАРИФИКАЦИЯ. Смотрите поле billedUnits в ответе: в нём ровно то число, которое списано.
     *
     * @param float $lat Широта в градусах. Для России — примерно от 41 до 82.
     * @param float $lon Долгота в градусах.
     * @param array{includeNeighbors?: bool, neighborsSize?: int, includeAdmin?: bool, includeNearby?: bool, nearbyRadiusKm?: float, nearbySize?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - includeNeighbors — Вернуть также следующие по близости дома.
     *     - neighborsSize — Сколько соседних домов вернуть.
     *     - includeAdmin — Вернуть административные образования, внутри границ которых лежит точка.
     *     - includeNearby — Вернуть ориентиры вокруг точки: магазины, аптеки, остановки.
     *     - nearbyRadiusKm — Радиус поиска ориентиров в километрах.
     *     - nearbySize — Сколько ориентиров вернуть.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - query (array<string, mixed>) — Точка, для которой искали адрес.
     *     - address (array<string, mixed>) — Ближайший адрес — ответ на заданный вопрос.
     *     - neighbors (list<array<string, mixed>>|null) — Следующие по близости дома.
     *     - adminAreas (list<array<string, mixed>>|null) — Административные образования, внутри границ которых лежит точка, от общего к частному.
     *     - nearby (list<array<string, mixed>>|null) — Ориентиры вокруг точки.
     *     - nearbyCompletenessNotice (string|null) — Оговорка о полноте данных по ориентирам.
     *     - billedUnits (int) — Сколько единиц работы списано за этот запрос: база плюс опции, которые действительно дали данные.
     *     - attribution (string) — Строка указания источника данных.
     *
     * @throws AtloriumException
     */
    public function lookup(float $lat, float $lon, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->lookup($lat, $lon, $params)->getData();

        return $data;
    }

    /**
     * Возвращает ориентиры вокруг точки: магазины, аптеки, остановки, заправки.
     *
     * Пример запроса:
     * GET /api/geocodereverse/nearby?lat=55.788226&lon=49.121984&radiusKm=1&size=10
     *
     * ЭТО НЕ ПЕРЕЧИСЛЕНИЕ СОСЕДНИХ ДОМОВ. Режим отвечает на вопрос «что здесь есть»:
     * организации и объекты городской среды. Соседние дома — это обогащение обратного
     * геокодинга (параметр includeNeighbors), и стоит оно по-другому.
     *
     * ⚠ ПОЛНОТА НЕ ГАРАНТИРУЕТСЯ, и это принципиально. Сведения об объектах наносят на карту
     * добровольцы: в крупных городах покрытие плотное, в малых населённых пунктах и вдоль
     * трасс — разреженное. Отсутствие объекта в ответе НЕ означает, что его нет в
     * действительности. Предупреждение об этом едет в каждом успешном ответе полем
     * completenessNotice — показывайте его пользователю, иначе он сделает неверный вывод.
     *
     * ПОЧЕМУ РАДИУС ОГРАНИЧЕН ДЕСЯТЬЮ КИЛОМЕТРАМИ. Граница смысловая, а не техническая: запрос
     * с радиусом в сотню километров — это уже не «что рядом», а постраничная выгрузка куска
     * карты.
     *
     * ТАРИФИКАЦИЯ. Одна единица работы за запрос. Пустой результат (404) не тарифицируется.
     *
     * @param float $lat Широта центра поиска в градусах.
     * @param float $lon Долгота центра поиска в градусах.
     * @param array{radiusKm?: float, size?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - radiusKm — Радиус поиска в километрах.
     *     - size — Сколько объектов вернуть.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - query (array<string, mixed>) — Центр поиска.
     *     - radiusKm (float) — Радиус поиска, километры.
     *     - places (list<array<string, mixed>>) — Найденные ориентиры, ближайший первым.
     *     - completenessNotice (string) — Постоянное предупреждение о природе данных: сведения об объектах наносят на карту добровольцы, поэтому отсутствие объекта в ответе не означ…
     *     - attribution (string) — Строка указания источника данных.
     *
     * @throws AtloriumException
     */
    public function nearby(float $lat, float $lon, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->nearby($lat, $lon, $params)->getData();

        return $data;
    }
}
