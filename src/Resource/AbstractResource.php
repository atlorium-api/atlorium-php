<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\BaseClient;

/**
 * Базовый класс ресурса (одного сервиса Atlorium).
 */
abstract class AbstractResource
{
    protected BaseClient $client;

    public function __construct(BaseClient $client)
    {
        $this->client = $client;
    }

    /**
     * Экранирует значение для подстановки в путь (/, пробелы, кириллица).
     *
     * @param string|int $value
     */
    protected static function segment($value): string
    {
        return rawurlencode((string) $value);
    }

    /**
     * Строка или массив строк → список (строка не разбивается на символы).
     *
     * @param string|array<array-key, string> $value
     *
     * @return list<string>
     */
    protected static function toList($value): array
    {
        return is_string($value) ? [$value] : array_values($value);
    }

    /**
     * Приводит к строке ответ, который по описанию API — строка, но может прийти и JSON-объектом:
     * строка — как есть, пустое тело — пустая строка, объект с полем message — это поле,
     * прочее — JSON.
     *
     * @param mixed $data разобранное тело ответа
     */
    protected static function text($data): string
    {
        if (is_string($data)) {
            return $data;
        }
        if ($data === null) {
            return '';
        }
        if (is_array($data) && isset($data['message']) && is_string($data['message'])) {
            return $data['message'];
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        return $json === false ? '' : $json;
    }
}
