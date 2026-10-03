<?php

declare(strict_types=1);

namespace Atlorium\Enum;

/**
 * Типовая заготовка расписания для простого конструктора.
 *
 * Значения:
 * * `EveryMinute` — Каждую минуту.
 * * `EveryNMinutes` — Каждые N минут; шаг задаётся полем интервала.
 * * `Hourly` — Раз в час, в начале часа.
 * * `EveryNHours` — Каждые N часов; шаг задаётся полем интервала.
 * * `Daily` — Раз в сутки, в заданное время.
 * * `Weekly` — В выбранные дни недели, в заданное время.
 * * `Monthly` — Раз в месяц, в заданное число и время.
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
final class CronTemplateType
{
    /** Каждую минуту. */
    public const EVERY_MINUTE = 0;

    /** Каждые N минут (шаг — interval). */
    public const EVERY_N_MINUTES = 1;

    /** Раз в час, в начале часа. */
    public const HOURLY = 2;

    /** Каждые N часов (шаг — interval). */
    public const EVERY_N_HOURS = 3;

    /** Раз в сутки, в timeOfDay. */
    public const DAILY = 4;

    /** В выбранные дни недели (weekDays), в timeOfDay. */
    public const WEEKLY = 5;

    /** Раз в месяц: число dayOfMonth, время timeOfDay. */
    public const MONTHLY = 6;

    private function __construct()
    {
    }
}
