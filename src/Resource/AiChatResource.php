<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API ИИ-чата: $client->aichat.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/ai-chat-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class AiChatResource extends AbstractResource
{
    private AiChatRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new AiChatRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): AiChatRawResource
    {
        return $this->raw;
    }

    /**
     * Отправляет сообщение ИИ и получает ответ.
     * Автоматически создаёт новую сессию или продолжает существующую.
     *
     * Пример запроса:
     * POST /api/aichat/send
     * Content-Type: application/json
     *
     * {
     *   "message": "Что такое DNS?",
     *   "sessionId": null
     * }
     *
     * Для продолжения диалога передайте sessionId из предыдущего ответа:
     * {
     *   "message": "А как работает DNSSEC?",
     *   "sessionId": "abc123def456"
     * }
     *
     * Ограничения:
     * - Длина сообщения ограничена в токенах и зависит от модели (см. maxInputTokens в GET /api/aichat/models)
     * - Максимум 50 сообщений пользователя в одной сессии
     * - Сессия удаляется через 1 час неактивности
     * - Поле model принимает только модели из GET /api/aichat/models; произвольное имя — 400
     *
     * ТАРИФИКАЦИЯ складывается из ДВУХ составляющих:
     * 1) плата за запрос — фиксированная, одинаковая для всех моделей;
     * 2) стоимость токенов выбранной модели — зависит от модели и от длины диалога.
     *    У бесплатных моделей она равна нулю, и тогда вы платите только за запрос.
     *    Цены токенов каждой модели — в GET /api/aichat/models (inputPricePer1kTokens,
     *    outputPricePer1kTokens) и на странице тарифов.
     *
     * В ответе возвращаются фактически израсходованные токены (promptTokens/completionTokens) —
     * именно по ним и считается вторая составляющая.
     *
     * ВАЖНО ПРО БАЛАНС: перед вызовом модели временно удерживается максимально возможная
     * стоимость запроса (весь ввод + самый длинный ответ), а списывается только фактически
     * израсходованное — остаток удержания сразу возвращается. Поэтому для запроса к дорогой
     * модели на балансе должен быть этот максимум, даже если по факту спишется меньше.
     * Если баланса не хватает на удержание — 402 Payment Required.
     *
     * ВРЕМЯ ОТВЕТА: ответ приходит целиком, а не частями, и перед ответом модель рассуждает.
     * Короткий ответ приходит за секунды, развёрнутый ответ флагманской модели — за несколько
     * минут. Ставьте таймаут HTTP-клиента не меньше 10 минут: если клиент оборвёт соединение
     * раньше, модель всё равно допишет ответ, и запрос будет оплачен как выполненный.
     * Не тарифицируются только сбои: модель не ответила за 10 минут или вернула ответ без
     * текста (израсходовала лимит ответа на рассуждения) — в обоих случаях код 500.
     *
     * @param string $message Текст сообщения пользователя (обязательное поле; длина ограничена в зависимости от выбранной модели).
     * @param array{sessionId?: string, model?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - sessionId — Идентификатор существующей сессии для продолжения диалога.
     *     - model — Идентификатор или алиас ИИ модели для обработки запроса.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - sessionId (string) — Идентификатор сессии — сохраните его для продолжения диалога.
     *     - reply (string) — Текст ответа ИИ модели.
     *     - model (string) — Идентификатор (или алиас) использованной модели.
     *     - promptTokens (int) — Количество токенов, потраченных на промт (входные данные).
     *     - completionTokens (int) — Количество токенов, потраченных на генерацию ответа.
     *     - totalTokens (int) — Общее количество использованных токенов (prompt + completion).
     *     - createdAt (string) — Время создания ответа в формате UTC.
     *
     * @throws AtloriumException
     */
    public function send(string $message, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->send($message, $params)->getData();

        return $data;
    }

    /**
     * Получает информацию о сессии чата по её идентификатору.
     * Возвращает количество сообщений, время создания и последней активности.
     *
     * Пример запроса:
     * GET /api/aichat/session/abc123def456789012345678abcdef00
     *
     * Сессии сохраняются и автоматически удаляются через 1 час неактивности.
     *
     * @param string $sessionId Уникальный идентификатор сессии чата. Формат: строка из 32 шестнадцатеричных символов (GUID без дефисов). Пример: abc123def456789012345678abcdef00 Идентификатор возвращается в ответе метода SendMessage при создании новой сессии.
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - sessionId (string) — Уникальный идентификатор сессии.
     *     - messageCount (int) — Количество сообщений пользователя в сессии (без ответов ИИ).
     *     - createdAt (string) — Время создания сессии (UTC).
     *     - lastActivityAt (string) — Время последней активности в сессии (UTC).
     *
     * @throws AtloriumException
     */
    public function getSession(string $sessionId, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->getSession($sessionId, $params)->getData();

        return $data;
    }

    /**
     * Удаляет сессию чата и всю историю сообщений.
     * После удаления сессию невозможно восстановить.
     *
     * Пример запроса:
     * DELETE /api/aichat/session/abc123def456789012345678abcdef00
     *
     * После удаления все сообщения сессии безвозвратно удаляются.
     * Повторная отправка сообщения с тем же sessionId создаст новую пустую сессию.
     *
     * @param string $sessionId Уникальный идентификатор сессии чата для удаления. Формат: строка из 32 шестнадцатеричных символов (GUID без дефисов). Пример: abc123def456789012345678abcdef00
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return string
     *
     * @throws AtloriumException
     */
    public function deleteSession(string $sessionId, array $params = []): string
    {
        return self::text($this->raw->deleteSession($sessionId, $params)->getData());
    }

    /**
     * Возвращает список доступных ИИ моделей.
     * Каждая модель имеет уникальный идентификатор, название, описание и лимит токенов.
     *
     * Пример запроса:
     * GET /api/aichat/models
     *
     * Список моделей может меняться. Этот метод бесплатный и не тарифицируется —
     * тарифицируется только POST /api/aichat/send. Чтобы обратиться к конкретной модели,
     * передайте её id в поле model запроса на отправку сообщения.
     *
     * Стоимость запроса к /api/aichat/send складывается из двух частей: фиксированной платы
     * за запрос (одинаковой для всех моделей) и стоимости токенов выбранной модели —
     * она у моделей РАЗНАЯ (inputPricePer1kTokens/outputPricePer1kTokens ниже, для бесплатных
     * моделей — ноль). Подробности — в описании метода POST /api/aichat/send.
     *
     * @param array{timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return list<array<string, mixed>>
     *     Поля ответа (каждого элемента):
     *     - id (string) — Идентификатор модели — его и нужно передавать в поле model запроса.
     *     - name (string) — Человекочитаемое название модели.
     *     - description (string) — Описание возможностей и особенностей модели.
     *     - contextWindowTokens (int) — Полное контекстное окно модели в токенах (вход + история + ответ).
     *     - maxInputTokens (int) — Лимит на ввод ОДНОГО сообщения пользователя в токенах.
     *     - maxOutputTokens (int) — Потолок длины ответа модели в токенах (уходит в max_tokens запроса).
     *     - maxTokens (int) — Устаревшее поле: дублирует contextWindowTokens и оставлено для совместимости.
     *     - inputPricePer1kTokens (float) — Цена ВХОДНЫХ токенов модели — в кредитах за 1000 токенов.
     *     - outputPricePer1kTokens (float) — Цена ВЫХОДНЫХ токенов (ответа модели) — в кредитах за 1000 токенов.
     *     - freeQuotaEligible (bool) — Действует ли на эту модель бесплатная дневная квота.
     *     - isAvailable (bool) — Доступна ли модель для запросов.
     *     - isLocal (bool) — Модель размещена на собственных серверах сервиса.
     *
     * @throws AtloriumException
     */
    public function listModels(array $params = []): array
    {
        /** @var list<array<string, mixed>> $data */
        $data = $this->raw->listModels($params)->getData();

        return $data;
    }
}
