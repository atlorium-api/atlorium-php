<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API проверки DNS: $client->dns.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/dns-lookup-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class DnsResource extends AbstractResource
{
    private DnsRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new DnsRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): DnsRawResource
    {
        return $this->raw;
    }

    /**
     * Выполняет DNS-запрос для основных типов записей (A, AAAA, MX, TXT, CNAME, NS).
     * Возвращает все найденные записи для указанного домена.
     *
     * Пример запроса:
     * GET /api/dns/lookup?domain=google.com
     * GET /api/dns/lookup?domain=microsoft.com&resolver=8.8.8.8
     *
     * Пример ответа:
     * {
     *   "domain": "google.com",
     *   "resolver": "1.1.1.1",
     *   "records": {
     *     "A": ["142.250.185.78"],
     *     "AAAA": ["2a00:1450:4010:c08::64"],
     *     "MX": ["smtp.google.com"],
     *     "NS": ["ns1.google.com", "ns2.google.com"]
     *   }
     * }
     *
     * @param string $domain Доменное имя для запроса. Формат: чистое доменное имя без протокола. Примеры: - example.com - sub.example.com - google.com Не допускаются: - URL с протоколом (https://example.com) - IP-адреса
     * @param array{resolver?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - resolver — IP-адрес DNS-резолвера для выполнения запроса (необязательный).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - domain (string) — Проверенное доменное имя.
     *     - resolver (string) — Резолвер, ответивший на запрос: адрес или узнаваемое имя публичного DNS-сервиса (например, Google или Cloudflare).
     *     - requestedAtUtc (string) — Момент выполнения запроса (UTC).
     *     - records (list<array<string, mixed>>) — Найденные записи, сгруппированные по типу.
     *     - errors (list<string>) — Ошибки резолва: домен не существует, таймаут, отказ сервера.
     *
     * @throws AtloriumException
     */
    public function lookup(string $domain, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->lookup($domain, $params)->getData();

        return $data;
    }

    /**
     * Проверяет распространение DNS-записей (пропагацию) через несколько публичных резолверов.
     * Полезно для проверки того, обновились ли DNS-записи на всех серверах после изменения.
     *
     * Пример запроса:
     * GET /api/dns/propagation?domain=example.com
     * GET /api/dns/propagation?domain=new-site.com&resolvers=8.8.8.8,1.1.1.1
     *
     * Пример использования:
     * После изменения DNS-записей домена выполните этот запрос,
     * чтобы проверить, распространились ли изменения на все резолверы.
     * Полная пропагация может занять от нескольких минут до 48 часов.
     *
     * @param string $domain Доменное имя для проверки. Формат: чистое доменное имя без протокола. Примеры: - example.com - new-domain.com
     * @param array{resolvers?: string|list<string>, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - resolvers — Список IP-адресов DNS-резолверов через запятую (необязательный).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - domain (string) — Проверенное доменное имя.
     *     - requestedAtUtc (string) — Момент выполнения запроса (UTC).
     *     - snapshots (list<array<string, mixed>>) — Ответы отдельных резолверов.
     *
     * @throws AtloriumException
     */
    public function checkPropagation(string $domain, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->checkPropagation($domain, $params)->getData();

        return $data;
    }
}
