<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API проверки SSL-сертификата: $client->certificate.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/ssl-certificate-check-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class CertificateResource extends AbstractResource
{
    private CertificateRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new CertificateRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): CertificateRawResource
    {
        return $this->raw;
    }

    /**
     * Проверяет SSL/TLS сертификат по доменному имени и порту.
     * Подключается к серверу, извлекает сертификат и возвращает подробную информацию о нём.
     *
     * Пример запроса:
     * GET /api/certificate?host=google.com
     * GET /api/certificate?host=mail.server.com&port=993
     *
     * Сервис устанавливает TLS-соединение с указанным хостом и портом,
     * извлекает представленный сервером сертификат и анализирует его содержимое.
     *
     * @param string $host Доменное имя сервера для проверки. Поддерживаемые форматы: - Чистое доменное имя: example.com - С поддоменом: www.example.com - IP-адрес: 192.168.1.1 Примеры: google.com, github.com, microsoft.com
     * @param array{port?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - port — Порт для подключения (необязательный, по умолчанию 443).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - isValid (bool) — Главный вердикт: годен ли сертификат прямо сейчас.
     *     - expirationDate (string) — Дата и время (UTC) окончания срока действия.
     *     - daysRemaining (int) — Сколько суток осталось до expirationDate.
     *     - issuer (string) — Удостоверяющий центр, выпустивший сертификат, в виде отличительного имени (DN).
     *     - subject (string) — Владелец сертификата в виде отличительного имени (DN).
     *     - subjectAlternativeNames (list<string>) — Полный список доменов, которые покрывает сертификат (расширение SAN).
     *     - errors (list<string>) — Причины, по которым сертификат признан негодным: истёкший срок, несовпадение имени хоста, разрыв цепочки доверия, самоподписанность.
     *     - tlsVersion (string) — Версия протокола, на которой удалось согласовать соединение: Tls13, Tls12 и т.д.
     *     - validFrom (string) — Дата и время (UTC) начала срока действия — с этого момента сертификат годен.
     *     - serialNumber (string) — Серийный номер, присвоенный удостоверяющим центром.
     *     - thumbprint (string) — Отпечаток — хеш сертификата в шестнадцатеричном виде.
     *     - signatureAlgorithm (string) — Алгоритм подписи сертификата, например sha256RSA.
     *     - publicKeyAlgorithm (string) — Алгоритм открытого ключа: RSA или ECDSA.
     *     - keySize (int) — Длина ключа в битах.
     *     - hasPrivateKey (bool) — Есть ли рядом с сертификатом закрытый ключ.
     *     - isSelfSigned (bool) — Сертификат подписан сам собой, а не удостоверяющим центром.
     *     - certificateChain (list<array<string, mixed>>) — Цепочка доверия от сертификата сайта к корневому сертификату.
     *     - connectionTime (string) — Сколько заняло установление TLS-соединения.
     *     - host (string) — Проверенный хост — доменное имя или IP-адрес.
     *     - port (int) — Проверенный порт.
     *     - checkedAt (string) — Момент, на который актуален результат проверки.
     *
     * @throws AtloriumException
     */
    public function check(string $host, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->check($host, $params)->getData();

        return $data;
    }

    /**
     * Проверяет SSL/TLS сертификат по полному URL.
     * Автоматически извлекает хост и порт из URL и выполняет проверку сертификата.
     *
     * Пример запроса:
     * GET /api/certificate/url?url=https://google.com
     * GET /api/certificate/url?url=https://mail.server.com:993
     *
     * Этот метод удобен, когда вы хотите проверить сертификат по URL,
     * который пользователь скопировал из браузера — без необходимости
     * разбирать URL на хост и порт вручную.
     *
     * @param string $url Полный URL для проверки. Поддерживаемые форматы: - Полный URL с протоколом: https://example.com - URL с портом: https://example.com:8443 - URL с путём: https://example.com:8443/api/v1 Примеры: - https://google.com - https://secure.bank.com:8443 - https://api.github.com/users Если порт не указан, используется стандартный порт 443.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - isValid (bool) — Главный вердикт: годен ли сертификат прямо сейчас.
     *     - expirationDate (string) — Дата и время (UTC) окончания срока действия.
     *     - daysRemaining (int) — Сколько суток осталось до expirationDate.
     *     - issuer (string) — Удостоверяющий центр, выпустивший сертификат, в виде отличительного имени (DN).
     *     - subject (string) — Владелец сертификата в виде отличительного имени (DN).
     *     - subjectAlternativeNames (list<string>) — Полный список доменов, которые покрывает сертификат (расширение SAN).
     *     - errors (list<string>) — Причины, по которым сертификат признан негодным: истёкший срок, несовпадение имени хоста, разрыв цепочки доверия, самоподписанность.
     *     - tlsVersion (string) — Версия протокола, на которой удалось согласовать соединение: Tls13, Tls12 и т.д.
     *     - validFrom (string) — Дата и время (UTC) начала срока действия — с этого момента сертификат годен.
     *     - serialNumber (string) — Серийный номер, присвоенный удостоверяющим центром.
     *     - thumbprint (string) — Отпечаток — хеш сертификата в шестнадцатеричном виде.
     *     - signatureAlgorithm (string) — Алгоритм подписи сертификата, например sha256RSA.
     *     - publicKeyAlgorithm (string) — Алгоритм открытого ключа: RSA или ECDSA.
     *     - keySize (int) — Длина ключа в битах.
     *     - hasPrivateKey (bool) — Есть ли рядом с сертификатом закрытый ключ.
     *     - isSelfSigned (bool) — Сертификат подписан сам собой, а не удостоверяющим центром.
     *     - certificateChain (list<array<string, mixed>>) — Цепочка доверия от сертификата сайта к корневому сертификату.
     *     - connectionTime (string) — Сколько заняло установление TLS-соединения.
     *     - host (string) — Проверенный хост — доменное имя или IP-адрес.
     *     - port (int) — Проверенный порт.
     *     - checkedAt (string) — Момент, на который актуален результат проверки.
     *
     * @throws AtloriumException
     */
    public function checkUrl(string $url, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->checkUrl($url, $params)->getData();

        return $data;
    }

    /**
     * Проверяет несколько SSL/TLS сертификатов одновременно (пакетная проверка).
     * Позволяет проверить до 10 хостов за один запрос параллельно.
     *
     * Пример запроса:
     * GET /api/certificate/batch?hosts=google.com,github.com,microsoft.com
     * GET /api/certificate/batch?hosts=mail1.com,mail2.com&port=993
     *
     * Все проверки выполняются параллельно для быстрого получения результатов.
     * Если один из сертификатов недоступен, вся операция может завершиться ошибкой.
     *
     * @param string|list<string> $hosts Список доменных имён через запятую. Формат: host1,host2,host3 Ограничения: - Минимум 1 хост - Максимум 10 хостов за один запрос - Пробелы вокруг запятых игнорируются - Повторы одного и того же хоста схлопываются (за одну проверку платите один раз) ТАРИФИКАЦИЯ: похостная. Каждый хост — отдельная проверка сертификата, поэтому пакет из N хостов стоит столько же, сколько N одиночных проверок, и на столько же уменьшает дневной бесплатный лимит. Оплачиваются хосты, по которым получен вердикт (недоступный сервер — тоже вердикт); при сбое всего запроса не списывается ничего. Примеры: - google.com,github.com,microsoft.com - google.com, github.com, microsoft.com (с пробелами — допустимо)
     * @param array{port?: int, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - port — Порт для подключения ко ВСЕМ хостам (необязательный, по умолчанию 443).
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return list<array<string, mixed>>
     *     Поля ответа (каждого элемента):
     *     - isValid (bool) — Главный вердикт: годен ли сертификат прямо сейчас.
     *     - expirationDate (string) — Дата и время (UTC) окончания срока действия.
     *     - daysRemaining (int) — Сколько суток осталось до expirationDate.
     *     - issuer (string) — Удостоверяющий центр, выпустивший сертификат, в виде отличительного имени (DN).
     *     - subject (string) — Владелец сертификата в виде отличительного имени (DN).
     *     - subjectAlternativeNames (list<string>) — Полный список доменов, которые покрывает сертификат (расширение SAN).
     *     - errors (list<string>) — Причины, по которым сертификат признан негодным: истёкший срок, несовпадение имени хоста, разрыв цепочки доверия, самоподписанность.
     *     - tlsVersion (string) — Версия протокола, на которой удалось согласовать соединение: Tls13, Tls12 и т.д.
     *     - validFrom (string) — Дата и время (UTC) начала срока действия — с этого момента сертификат годен.
     *     - serialNumber (string) — Серийный номер, присвоенный удостоверяющим центром.
     *     - thumbprint (string) — Отпечаток — хеш сертификата в шестнадцатеричном виде.
     *     - signatureAlgorithm (string) — Алгоритм подписи сертификата, например sha256RSA.
     *     - publicKeyAlgorithm (string) — Алгоритм открытого ключа: RSA или ECDSA.
     *     - keySize (int) — Длина ключа в битах.
     *     - hasPrivateKey (bool) — Есть ли рядом с сертификатом закрытый ключ.
     *     - isSelfSigned (bool) — Сертификат подписан сам собой, а не удостоверяющим центром.
     *     - certificateChain (list<array<string, mixed>>) — Цепочка доверия от сертификата сайта к корневому сертификату.
     *     - connectionTime (string) — Сколько заняло установление TLS-соединения.
     *     - host (string) — Проверенный хост — доменное имя или IP-адрес.
     *     - port (int) — Проверенный порт.
     *     - checkedAt (string) — Момент, на который актуален результат проверки.
     *
     * @throws AtloriumException
     */
    public function checkBatch($hosts, array $params = []): array
    {
        /** @var list<array<string, mixed>> $data */
        $data = $this->raw->checkBatch($hosts, $params)->getData();

        return $data;
    }
}
