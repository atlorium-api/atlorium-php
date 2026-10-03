<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API разбора Cron-выражений: $client->cron.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/cron-expression-parser-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class CronResource extends AbstractResource
{
    private CronRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new CronRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): CronRawResource
    {
        return $this->raw;
    }

    /**
     * Парсит и валидирует cron-выражение, возвращает ближайшие даты запуска.
     * Позволяет проверить корректность выражения и увидеть, когда оно сработает.
     *
     * Пример запроса:
     * POST /api/cron/evaluate
     * Body: {
     *   "expression": "0 9 * * 1-5",
     *   "timeZoneId": "Europe/Moscow",
     *   "take": 5
     * }
     *
     * Пример ответа:
     * {
     *   "isValid": true,
     *   "description": "В 09:00, по будням с понедельника по пятницу",
     *   "nextOccurrences": ["2024-01-15T09:00:00+03:00", ...]
     * }
     *
     * @param string $expression Cron-выражение (5 или 6 полей, допускается ? из Quartz).
     * @param array{timeZoneId?: string, fromUtc?: \DateTimeInterface|string, take?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - timeZoneId — Часовой пояс для расчёта (Id из TimeZoneInfo).
     *     - fromUtc — Стартовая точка расчёта (UTC).
     *     - take — Количество ближайших запусков (1-100).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - isValid (bool) — Разобралось ли выражение.
     *     - rawExpression (string) — Выражение ровно в том виде, в каком его прислали, — без правок и нормализации.
     *     - normalizedExpression (string) — Приведённое к единому виду выражение: подставлены пропущенные поля, знак ?
     *     - error (string) — Причина, по которой выражение признано некорректным: неверное число полей, значение вне диапазона, нераспознанный символ.
     *     - warnings (list<string>) — Предупреждения о выражении, которое формально верно, но почти наверняка сработает не так, как задумано.
     *     - segments (list<string>) — Пояснение к каждому полю выражения по-человечески: «минуты: каждые 15», «день недели: понедельник».
     *     - occurrences (list<string>) — Ближайшие моменты срабатывания расписания, по возрастанию.
     *     - timeZone (array<string, mixed>) — Часовой пояс, в котором посчитаны запуски.
     *
     * @throws AtloriumException
     */
    public function evaluate(string $expression, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->evaluate($expression, $params)->getData();

        return $data;
    }

    /**
     * Генерирует cron-строку по заданному шаблону и параметрам.
     * Упрощает создание cron-выражений для типовых сценариев.
     *
     * @param array{template?: int, interval?: int, timeOfDay?: string, dayOfMonth?: int, weekDays?: list<int>, includeSeconds?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - template — Заготовка расписания.
     *     - interval — Шаг для шаблонов «каждые N минут» и «каждые N часов»; остальными шаблонами игнорируется.
     *     - timeOfDay — Время суток для суточного, недельного и месячного расписаний.
     *     - dayOfMonth — Число месяца (1–31) для месячного расписания.
     *     - weekDays — Дни недели для недельного расписания.
     *     - includeSeconds — Добавлять ли в выражение поле секунд.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - expression (string) — Готовое cron-выражение.
     *     - warnings (list<string>) — Замечания к собранному расписанию: оно рабочее, но сработает не всегда так, как ждут.
     *     - error (string) — Причина, по которой выражение собрать не удалось: недопустимый интервал, не выбран ни один день недели.
     *     - success (bool) — Удалось ли собрать выражение.
     *
     * @throws AtloriumException
     */
    public function build(array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->build($params)->getData();

        return $data;
    }
}
