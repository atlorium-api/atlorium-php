<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API профиля IP: $client->ipinfo.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/ip-geolocation-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class IpInfoResource extends AbstractResource
{
    private IpInfoRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new IpInfoRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): IpInfoRawResource
    {
        return $this->raw;
    }

    /**
     * Возвращает профиль IP-адреса: геолокацию, а при extended=true — ещё и сведения о владельце
     * и репутации адреса.
     *
     * Примеры запросов:
     * GET /api/ipinfo/8.8.8.8
     * GET /api/ipinfo/206.189.233.36?extended=true
     *
     * @param string $ip IP-адрес (IPv4 или IPv6). Примеры: 8.8.8.8, 2606:4700:4700::1111. Приватные и зарезервированные адреса публичного профиля не имеют.
     * @param array{extended?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - extended — Расширенные сведения: владелец сети (ASN/организация), hostname, признаки анонимизации (VPN/прокси/Tor/relay/резидентный прокси), контакт для жалоб и отметка о жалобах.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - ip (string) — Запрошенный IP-адрес в нормализованном виде.
     *     - tier ('Basic'|'Extended') — Какой объём сведений внутри (см.
     *     - isBogon (bool) — true — адрес из «служебного» диапазона (bogon): частный/зарезервированный/loopback и т.п.
     *     - hostname (string|null) — Обратное DNS-имя (PTR) хоста, если известно.
     *     - geo (array<string, mixed>|null) — Основная география адреса — наиболее достоверный из доступных вариантов.
     *     - geoSources (list<array<string, mixed>>|null) — Расходящиеся варианты географии, когда данные по адресу не совпадают между собой.
     *     - asn (array<string, mixed>|null) — Сведения об автономной системе (ASN) — «кто держит» сеть.
     *     - asnSources (list<array<string, mixed>>|null) — Расходящиеся варианты сведений об автономной системе.
     *     - company (array<string, mixed>|null) — Организация-владелец диапазона (может отличаться от ASN-оператора).
     *     - flags (array<string, mixed>|null) — Признаки сети (хостинг/мобильный/anycast/спутник и т.п.).
     *     - carrier (array<string, mixed>|null) — Данные мобильного оператора — только для мобильных сетей.
     *     - privacy (array<string, mixed>|null) — Признаки анонимизации (VPN/proxy/Tor/relay/резидентный прокси).
     *     - abuse (array<string, mixed>|null) — Контакт для жалоб (abuse).
     *     - hostedDomains (array<string, mixed>|null) — Сведения о доменах, размещённых на адресе (сейчас — только счётчик).
     *     - abuseReports (array<string, mixed>|null) — Отметка о жалобах на адрес (публичные базы репутации).
     *
     * @throws AtloriumException
     */
    public function lookup(string $ip, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->lookup($ip, $params)->getData();

        return $data;
    }
}
