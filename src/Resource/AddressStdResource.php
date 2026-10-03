<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API стандартизации адреса: $client->addressstd.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/address-standardization-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class AddressStdResource extends AbstractResource
{
    private AddressStdRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new AddressStdRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): AddressStdRawResource
    {
        return $this->raw;
    }

    /**
     * Разбирает адресную строку в структуру: адресные уровни (регион/город/улица/дом/квартира),
     * нормализованное однострочное представление и коэффициент качества.
     *
     * Примеры запросов:
     * GET /api/addressstd?address=г Москва, ул. 16-я Парковая д.2 к1 кв.3
     * GET /api/addressstd?address=ленинский проспект 4&region=77
     *
     * @param string $address Адресная строка. Примеры: «мск ленинскй проспект д4 кв5», «г Москва, ул. 16-я Парковая д.2 к1 кв.3». Допускаются опечатки, сокращения, нижний регистр и переставленные части.
     * @param array{region?: int, bind?: bool, geocode?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - region — Код региона по умолчанию (2 цифры, например 77), если в строке регион не указан — помогает разобрать «улица + дом» без верхнего уровня.
     *     - bind — Дополнить разбор привязкой к государственному адресному реестру (ГАР/ФИАС).
     *     - geocode — Дополнить разбор географическими координатами адреса.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - input (string) — Исходная строка (эхо запроса).
     *     - normalized (string) — Нормализованный адрес одной строкой (собран из разобранных элементов).
     *     - quality (int) — Коэффициент качества разбора, 0..100.
     *     - countryCode (string|null) — Код страны (RU/EN/US…), если удалось определить.
     *     - message (string|null) — Сообщение о неточности/проблеме, влияющей на качество (например, непонятный фрагмент).
     *     - info (string|null) — Информационное сообщение, НЕ влияющее на качество.
     *     - components (list<array<string, mixed>>) — Разобранные элементы адреса, сверху вниз (от страны к квартире).
     *     - extra (array<string, mixed>|null) — Второстепенные элементы, не вписавшиеся в общую модель (этаж, а/я, очередь, индекс из текста, нормализованное название организации).
     *     - milliseconds (int) — Время разбора в миллисекундах.
     *     - gar (array<string, mixed>|null) — Привязка к государственному адресному реестру (ГАР/ФИАС).
     *     - geo (array<string, mixed>|null) — Географические координаты адреса.
     *
     * @throws AtloriumException
     */
    public function standardize(string $address, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->standardize($address, $params)->getData();

        return $data;
    }
}
