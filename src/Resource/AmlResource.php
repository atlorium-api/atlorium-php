<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API AML-скоринга: $client->aml.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/aml-crypto-screening-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class AmlResource extends AbstractResource
{
    private AmlRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new AmlRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): AmlRawResource
    {
        return $this->raw;
    }

    /**
     * Выполняет AML-скрининг криптокошелька по адресу и сети.
     *
     * Пример запроса:
     * POST /api/aml/screen
     * { "walletAddress": "0x26a5919cd0392df875a53873b0323c42094bb216", "blockchain": "BNB" }
     *
     * Тарификация: списывается только успешный скрининг. Если сервис временно недоступен или
     * входные данные некорректны, списания не происходит.
     *
     * @param string $walletAddress Адрес криптокошелька для проверки (обязателен).
     * @param string $blockchain Сеть (блокчейн) адреса: "BTC", "ETH", "BNB", "TRX", "SOL" и т.д. (обязательно). Должна входить в список поддерживаемых сетей AmlBlockchains.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - walletAddress (string) — Проверённый адрес криптокошелька (в том виде, в котором по нему выполнялся скрининг).
     *     - blockchain (string) — Идентификатор сети (блокчейна), в которой выполнялся скрининг: например, "BTC", "ETH", "BNB", "TRX", "SOL".
     *     - screeningType (string) — Тип скрининга.
     *     - riskScore (int) — Нормализованный балл риска от 0 до 100, где 0 — риск не обнаружен, 100 — максимальный риск.
     *     - severity (string) — Уровень серьёзности риска — человеко-понятная категория поверх riskScore.
     *     - status (string) — Статус скрининга: "SCREENED" (проверка выполнена, результат готов), "PENDING" (поставлен в очередь — для on-demand обычно не встречается),…
     *     - summary (string|null) — Краткая человеко-читаемая сводка результата (например, «Риск не обнаружен (0/100)»).
     *     - dominantRiskCategory (string|null) — Доминирующая категория риска — категория с наибольшим вкладом в итоговый балл (например, "mixer", "sanctioned", "scam", "darknet").
     *     - sanctionsHit (bool) — true, если у кошелька обнаружена прямая или косвенная экспозиция на санкционные адреса/ организации.
     *     - pepCounterparty (bool) — true, если среди контрагентов кошелька есть связь с публичным должностным лицом (PEP — Politically Exposed Person).
     *     - totalValueUsd (float|null) — Суммарный оборот кошелька в долларах США, если он оценён.
     *     - feeUsd (float|null) — Суммарные комиссии по операциям кошелька в долларах США (если оценены).
     *     - sourceOfFunds (list<array<string, mixed>>) — Источники средств — откуда на кошелёк приходили деньги, в разбивке по категориям контрагентов и долям.
     *     - destinationOfFunds (list<array<string, mixed>>) — Назначение средств — куда с кошелька уходили деньги, в разбивке по категориям контрагентов и долям (destination of funds).
     *     - counterpartyConnections (list<array<string, mixed>>) — Контрагенты кошелька — организации/адреса, с которыми он напрямую или косвенно связан, с уровнем риска, категориями и оборотами.
     *     - networkNodes (list<array<string, mixed>>) — Узлы графа связей кошелька (для визуализации сети контактов).
     *     - networkEdges (list<array<string, mixed>>) — Рёбра графа связей кошелька — переводы между узлами networkNodes.
     *     - elapsedMs (int) — Время выполнения запроса в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function screen(string $walletAddress, string $blockchain, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->screen($walletAddress, $blockchain, $params)->getData();

        return $data;
    }

    /**
     * Проверяет входные данные AML-скрининга: поддержку сети и формат адреса.
     * Проверяет формат, а не существование адреса в блокчейне.
     *
     * Пример запроса:
     * GET /api/aml/validate?walletAddress=0x26a5919cd0392df875a53873b0323c42094bb216&blockchain=BNB
     *
     * @param string $walletAddress Проверяемый адрес кошелька.
     * @param string $blockchain Код сети (например, "ETH", "BTC", "BNB").
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - normalizedAddress (string) — Нормализованный адрес (убраны пробелы; для EVM-сетей приведён к нижнему регистру).
     *     - normalizedBlockchain (string) — Нормализованный код сети (верхний регистр), как он будет передан в скрининг.
     *     - isValid (bool) — true, если входные данные прошли локальную проверку формата (сеть поддерживается, адрес непустой и правдоподобен по длине/составу).
     *     - message (string) — Понятное человеку пояснение результата (что не так, либо «входные данные корректны»).
     *
     * @throws AtloriumException
     */
    public function validate(string $walletAddress, string $blockchain, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->validate($walletAddress, $blockchain, $params)->getData();

        return $data;
    }
}
