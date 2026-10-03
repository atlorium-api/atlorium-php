<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API погодных данных: $client->weather.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/weather-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class WeatherResource extends AbstractResource
{
    private WeatherRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new WeatherRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): WeatherRawResource
    {
        return $this->raw;
    }

    /**
     * Получает текущие погодные данные для указанных географических координат.
     *
     * Пример запроса для Москвы:
     * GET /api/weather?latitude=55.7558&longitude=37.6173
     *
     * Пример запроса для Санкт-Петербурга:
     * GET /api/weather?latitude=59.9343&longitude=30.3351
     *
     * Ограничения:
     * - При превышении лимита запросов возвращается код 429
     * - Координаты должны быть в допустимых диапазонах
     *
     * @param float $latitude Географическая широта в градусах (десятичный формат). Допустимые значения: от -90.0 до +90.0 - Положительные значения: Северное полушарие (например, 55.7558 — Москва) - Отрицательные значения: Южное полушарие (например, -33.8688 — Сидней) - 0: Экватор Примеры: 55.7558 (Москва), 51.5074 (Лондон), 40.7128 (Нью-Йорк)
     * @param float $longitude Географическая долгота в градусах (десятичный формат). Допустимые значения: от -180.0 до +180.0 - Положительные значения: Восточное полушарие (например, 37.6173 — Москва) - Отрицательные значения: Западное полушарие (например, -74.0060 — Нью-Йорк) - 0: Нулевой меридиан (Гринвич) Примеры: 37.6173 (Москва), -0.1278 (Лондон), -74.0060 (Нью-Йорк)
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - coord (array<string, mixed>) — Координаты точки, для которой отдана погода.
     *     - weather (list<array<string, mixed>>) — Погодные явления в точке.
     *     - base (string) — Служебная пометка метеорологической службы.
     *     - main (array<string, mixed>) — Основные измеримые показатели: температура, давление, влажность.
     *     - visibility (int|null) — Дальность видимости в метрах.
     *     - wind (array<string, mixed>) — Ветер: скорость, направление и порывы.
     *     - clouds (array<string, mixed>) — Облачность.
     *     - rain (array<string, mixed>|null) — Объём выпавшего дождя.
     *     - snow (array<string, mixed>|null) — Объём выпавшего снега.
     *     - dt (int) — Момент, на который актуальны данные, в формате Unix-времени (секунды с 1 января 1970 года, UTC).
     *     - sys (array<string, mixed>) — Сведения о стране и о времени восхода и заката.
     *     - timezone (int) — Сдвиг местного времени точки относительно UTC в секундах.
     *     - id (int) — Идентификатор населённого пункта в справочнике метеорологических станций.
     *     - name (string) — Название ближайшего населённого пункта.
     *     - cod (int) — Служебный код метеорологической сводки (200 — успех).
     *
     * @throws AtloriumException
     */
    public function get(float $latitude, float $longitude, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->get($latitude, $longitude, $params)->getData();

        return $data;
    }
}
