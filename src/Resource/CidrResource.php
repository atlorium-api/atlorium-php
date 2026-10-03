<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API калькулятора подсетей: $client->cidr.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/cidr-subnet-calculator-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class CidrResource extends AbstractResource
{
    private CidrRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new CidrRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): CidrRawResource
    {
        return $this->raw;
    }

    /**
     * Вычисляет все параметры CIDR-блока.
     *
     * Пример запроса: GET /api/cidr/calculate?ip=192.168.1.0&mask=24
     *
     * Возвращает:
     * - Network Address (адрес сети)
     * - Broadcast Address (широковещательный адрес)
     * - Netmask (маска подсети)
     * - Wildcard Mask (wildcard-маска)
     * - First/Last Usable IP (первый/последний используемый IP)
     * - Total/Usable Hosts (общее/используемое количество хостов)
     * - Binary/Hex представления
     *
     * @param string $ip IP-адрес (IPv4 или IPv6), например: 192.168.1.0 или 2001:db8::1
     * @param int $mask Маска подсети в CIDR-нотации (0-32 для IPv4, 0-128 для IPv6)
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - ipAddress (string) — Исходный IP-адрес, введённый пользователем.
     *     - mask (int) — Маска подсети в CIDR-нотации (например, 24 для /24).
     *     - ipVersion (int) — Версия IP-протокола: 4 для IPv4, 6 для IPv6.
     *     - networkAddress (string) — Адрес сети (Network Address) — первый адрес в диапазоне.
     *     - broadcastAddress (string) — Широковещательный адрес (Broadcast Address) — последний адрес в диапазоне.
     *     - netmask (string) — Маска подсети в полном формате (например, 255.255.255.0).
     *     - wildcardMask (string) — Wildcard-маска — инверсия маски подсети (используется в ACL Cisco).
     *     - firstUsableIp (string) — Первый используемый IP-адрес (Network + 1 для IPv4).
     *     - lastUsableIp (string) — Последний используемый IP-адрес (Broadcast - 1 для IPv4).
     *     - totalHosts (string) — Общее количество хостов в сети.
     *     - usableHosts (string) — Количество используемых хостов (без Network и Broadcast для IPv4).
     *     - cidrNotation (string) — CIDR-нотация сети (например, 192.168.1.0/24).
     *     - binaryRepresentation (string) — Двоичное представление IP-адреса.
     *     - hexRepresentation (string) — Шестнадцатеричное представление IP-адреса.
     *     - binaryMask (string) — Двоичное представление маски подсети.
     *     - success (bool) — Флаг успешности расчёта.
     *     - error (string|null) — Сообщение об ошибке, если расчёт не удался.
     *
     * @throws AtloriumException
     */
    public function calculate(string $ip, int $mask, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->calculate($ip, $mask, $params)->getData();

        return $data;
    }

    /**
     * Разбивает CIDR-блок на подсети с указанной новой маской.
     *
     * Пример запроса: GET /api/cidr/subnets?cidr=192.168.0.0/16&newMask=24
     *
     * Если количество подсетей превышает 128, возвращаются только первые 128.
     * Флаг IsTruncated указывает на усечение результата.
     *
     * @param string $cidr Исходный CIDR, например: 192.168.0.0/16
     * @param int $newMask Новая маска для подсетей (должна быть больше текущей)
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - originalCidr (string) — Исходный CIDR, который разбивается на подсети.
     *     - newMask (int) — Новая маска для подсетей.
     *     - totalSubnets (string) — Общее количество возможных подсетей.
     *     - displayedCount (int) — Количество отображаемых подсетей (до 128).
     *     - isTruncated (bool) — Флаг, указывающий что результат был ограничен.
     *     - subnets (list<array<string, mixed>>) — Список подсетей.
     *     - success (bool) — Флаг успешности расчёта.
     *     - error (string|null) — Сообщение об ошибке, если расчёт не удался.
     *
     * @throws AtloriumException
     */
    public function split(string $cidr, int $newMask, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->split($cidr, $newMask, $params)->getData();

        return $data;
    }

    /**
     * Проверяет, находится ли указанный IP-адрес в пределах CIDR-диапазона.
     *
     * Пример запроса: GET /api/cidr/check?cidr=10.0.0.0/8&ip=10.5.5.5
     *
     * @param string $cidr CIDR-диапазон для проверки, например: 10.0.0.0/8
     * @param string $ip IP-адрес для проверки, например: 10.5.5.5
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - cidr (string) — Проверяемый CIDR-диапазон.
     *     - ipAddress (string) — Проверяемый IP-адрес.
     *     - isInRange (bool) — Результат проверки: true — IP находится в диапазоне.
     *     - details (string) — Детальное описание результата проверки.
     *     - success (bool) — Флаг успешности проверки (валидность входных данных).
     *     - error (string|null) — Сообщение об ошибке, если проверка не удалась.
     *
     * @throws AtloriumException
     */
    public function check(string $cidr, string $ip, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->check($cidr, $ip, $params)->getData();

        return $data;
    }

    /**
     * Вычисляет наименьший общий CIDR (Supernet), покрывающий все входные IP/CIDR.
     *
     * Пример запроса: POST /api/cidr/supernet
     * Body: { "inputs": ["192.168.1.0/24", "192.168.2.0/24", "192.168.3.0/24"] }
     *
     * Поддерживаемые форматы входных данных:
     * - CIDR: 192.168.0.0/24
     * - Одиночный IP: 192.168.1.1
     *
     * Смешивание IPv4 и IPv6 не поддерживается.
     *
     * @param string|list<string> $inputs Список входных IP-адресов и/или CIDR для агрегации. Поддерживаемые разделители: новая строка, запятая, точка с запятой, пробел.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - inputs (list<string>) — Список входных IP-адресов и CIDR.
     *     - parsedCount (int) — Количество успешно разобранных входных значений.
     *     - supernetCidr (string) — Вычисленный агрегированный CIDR (Supernet).
     *     - networkAddress (string) — Адрес сети supernet.
     *     - broadcastAddress (string) — Широковещательный адрес supernet.
     *     - totalHosts (string) — Общее количество хостов в supernet.
     *     - success (bool) — Флаг успешности расчёта.
     *     - error (string|null) — Сообщение об ошибке, если расчёт не удался.
     *     - warnings (list<string>) — Список предупреждений (например, если некоторые входные значения не распознаны).
     *
     * @throws AtloriumException
     */
    public function supernet($inputs, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->supernet($inputs, $params)->getData();

        return $data;
    }
}
