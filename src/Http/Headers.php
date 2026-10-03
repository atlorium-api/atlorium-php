<?php

declare(strict_types=1);

namespace Atlorium\Http;

/**
 * Служебные операции над HTTP-заголовками: нормализация, поиск без учёта регистра,
 * разбор Retry-After.
 *
 * @internal Не является частью публичного API, может меняться без предупреждения.
 */
final class Headers
{
    private function __construct()
    {
    }

    /**
     * Приводит заголовки к виду «имя в нижнем регистре => список значений».
     *
     * @param array<array-key, mixed> $headers
     *
     * @return array<string, list<string>>
     */
    public static function normalize(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $value) {
            $key = strtolower(trim((string) $name));
            if ($key === '') {
                continue;
            }

            $values = is_array($value) ? $value : [$value];
            foreach ($values as $item) {
                if (is_scalar($item)) {
                    $result[$key][] = (string) $item;
                }
            }
        }

        return $result;
    }

    /**
     * Значение заголовка без учёта регистра имени; несколько значений склеиваются через «, ».
     *
     * @param array<string, list<string>> $headers нормализованные заголовки
     */
    public static function get(array $headers, string $name): ?string
    {
        $key = strtolower($name);
        if (!isset($headers[$key]) || $headers[$key] === []) {
            return null;
        }

        return implode(', ', $headers[$key]);
    }

    /**
     * Разбирает Retry-After: число секунд или HTTP-дата (RFC 9110). Возвращает секунды
     * ожидания (не меньше нуля) или null, если заголовок пуст либо не распознан.
     */
    public static function parseRetryAfter(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(0.0, (float) $value);
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return (float) max(0, $timestamp - time());
    }
}
