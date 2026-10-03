<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API проверки ссылки: $client->urlcheck.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/url-reputation-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class UrlCheckResource extends AbstractResource
{
    private UrlCheckRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new UrlCheckRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): UrlCheckRawResource
    {
        return $this->raw;
    }

    /**
     * Проверяет одну ссылку и возвращает вердикт репутационной проверки.
     *
     * Пример запроса:
     * GET /api/urlcheck?url=https://example.com/page
     *
     * Пример ответа «чисто»:
     * { "url": "https://example.com/page", "verdict": "clean", "threatTypes": [],
     *   "checkedAtUtc": "2026-09-01T10:15:00Z" }
     *
     * Пример ответа «угроза»:
     * { "url": "http://danger.example/login", "verdict": "threat",
     *   "threatTypes": ["social_engineering"], "checkedAtUtc": "2026-09-01T10:15:00Z" }
     *
     * В демо-режиме (публичный демо-ключ) ветку ответа задаёт домен: адрес на
     * malware.example.com вернёт вредонос, на phishing.example.com — фишинг, на
     * danger.example.com — сразу две категории, любой другой — «чисто».
     *
     * @param string $url Проверяемый адрес. Схему можно опустить: «example.com/page» будет приведено к «http://example.com/page». Принимаются только адреса со схемой http или https. В ответе адрес возвращается в приведённом виде — именно в нём он и проверялся.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - url (string) — Проверенная ссылка в НОРМАЛИЗОВАННОМ виде — так, как она реально уходила на проверку.
     *     - verdict ('clean'|'threat'|'unknown') — Итог проверки: чисто, угроза или «не удалось проверить».
     *     - threatTypes (list<'malware'|'social_engineering'|'unwanted_software'>) — Категории угрозы.
     *     - checkedAtUtc (string) — Момент, к которому относится вердикт (UTC).
     *     - message (string) — Короткое пояснение для человека: почему такой вердикт и что с ним делать.
     *     - elapsedMs (int) — Сколько миллисекунд заняла проверка.
     *
     * @throws AtloriumException
     */
    public function check(string $url, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->check($url, $params)->getData();

        return $data;
    }

    /**
     * Проверяет пакет ссылок за один вызов (до 100 адресов).
     *
     * Порядок результатов совпадает с порядком присланных ссылок — сопоставляйте по индексу.
     *
     * Пример запроса:
     * POST /api/urlcheck/batch
     * [ "https://example.com", "http://phishing.example.com/login" ]
     *
     * ТАРИФИКАЦИЯ ПАКЕТА. Списывается по числу ссылок с НАСТОЯЩИМ вердиктом. Если по трём
     * строкам из ста вердикт получить не удалось, спишется девяносто семь единиц, а не сто.
     *
     * @param string|list<string> $urls Список проверяемых адресов. Пустые строки и повторы отбрасываются до проверки: повтор не добавляет работы и оплачиваться не должен. Максимум — 100 адресов.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - results (list<array<string, mixed>>) — Результаты по каждой ссылке — в том же порядке, в каком ссылки пришли в запросе.
     *     - total (int) — Сколько ссылок было в пакете (после удаления пустых строк и дубликатов).
     *     - threatCount (int) — Сколько ссылок отмечено как опасные.
     *     - unknownCount (int) — Сколько ссылок проверить не удалось (вердикт unknown).
     *     - elapsedMs (int) — Общее время обработки пакета в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function checkBatch($urls, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->checkBatch($urls, $params)->getData();

        return $data;
    }
}
