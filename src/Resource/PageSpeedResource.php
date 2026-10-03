<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;

/**
 * API аудита сайта: $client->pagespeed.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/core-web-vitals-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class PageSpeedResource extends AbstractResource
{
    private PageSpeedRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new PageSpeedRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): PageSpeedRawResource
    {
        return $this->raw;
    }

    /**
     * Выполняет аудит одной страницы и возвращает полный отчёт.
     *
     * Примеры запросов:
     * GET /api/pagespeed?url=https://example.com
     * GET /api/pagespeed?url=https://example.com&strategy=desktop
     * GET /api/pagespeed?url=https://example.com&forceRefresh=true
     *
     * ⚠ Ответ занимает 10–30 секунд: страница действительно загружается в браузере.
     *
     * КАК ЧИТАТЬ ОТЧЁТ. Начните с поля message — это одна фраза с выводом. Затем сверьте
     * finalUrl с запрошенным адресом: если они различаются, вас увела переадресация и
     * измерялась другая страница (частый случай — редирект на страницу согласия с cookie).
     * Дальше — performanceScore как быстрый ориентир и opportunities сверху вниз: первые
     * два-три пункта и есть то, чем стоит заняться.
     *
     * ЧТО ЗНАЧИТ «ЭКОНОМИЯ» В РЕКОМЕНДАЦИЯХ. Это оценка, а не обещание: столько
     * миллисекунд отыграется, если пункт выполнить полностью. Пункты пересекаются между
     * собой, поэтому складывать экономию по всему списку и ждать такой суммы не следует.
     *
     * @param string $url Адрес страницы. Схему можно опустить: «example.com» будет приведено к «https://example.com/». Принимаются только адреса со схемой http или https. Страница должна открываться публично — без авторизации и без блокировки автоматических обращений.
     * @param array{strategy?: 'mobile'|'desktop', forceRefresh?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - strategy — Профиль устройства: mobile (по умолчанию) или desktop.
     *     - forceRefresh — true — выполнить прогон заново, минуя сохранённый результат.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - url (string) — Адрес, по которому запрашивался аудит (в нормализованном виде).
     *     - finalUrl (string) — Адрес, на котором прогон в итоге оказался.
     *     - strategy ('mobile'|'desktop') — Профиль устройства, на котором выполнен прогон.
     *     - fetchedAtUtc (string) — Момент выполнения прогона (UTC).
     *     - performanceScore (int|null) — Балл производительности, 0–100.
     *     - accessibilityScore (int|null) — Балл доступности, 0–100.
     *     - bestPracticesScore (int|null) — Балл соблюдения рекомендуемых практик, 0–100.
     *     - seoScore (int|null) — Балл поисковой оптимизации, 0–100.
     *     - labMetrics (list<array<string, mixed>>) — Лабораторные метрики прогона.
     *     - fieldData (array<string, mixed>|null) — Полевые данные по ЭТОЙ странице.
     *     - originFieldData (array<string, mixed>|null) — Полевые данные по САЙТУ ЦЕЛИКОМ.
     *     - opportunities (list<array<string, mixed>>) — Рекомендации, отсортированные по убыванию оценочного выигрыша.
     *     - elapsedMs (int) — Сколько миллисекунд занял аудит на нашей стороне.
     *     - message (string) — Пояснение для человека: краткий вывод по отчёту.
     *
     * @throws AtloriumException
     */
    public function audit(string $url, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->audit($url, $params)->getData();

        return $data;
    }

    /**
     * Выполняет аудит пакета адресов за один вызов (до 20 адресов).
     *
     * Порядок результатов совпадает с порядком присланных адресов — сопоставляйте по индексу.
     *
     * ⚠ ПАКЕТ ИДЁТ МИНУТАМИ. Двадцать адресов — примерно две минуты: адреса обрабатываются
     * по четыре одновременно, каждый прогон 10–30 секунд. Поставьте таймаут своего клиента
     * с запасом или разбейте работу на пакеты поменьше — в деньгах вы ничего не потеряете,
     * единица стоит столько же.
     *
     * Пример запроса:
     * POST /api/pagespeed/batch?strategy=mobile
     * [ "https://example.com", "https://example.com/catalog" ]
     *
     * ТАРИФИКАЦИЯ ПАКЕТА. Списывается по числу адресов, по которым аудит РЕАЛЬНО состоялся.
     * Если из двадцати страниц три не открылись, спишется семнадцать единиц, а не двадцать.
     * Принудительного обновления в обход сохранённых отчётов у пакетного режима нет: пакет —
     * это регулярный обход списка страниц, и в нём ценно как раз то, что уже посчитанное
     * берётся готовым.
     *
     * @param string|list<string> $urls Список адресов страниц. Пустые строки и повторы отбрасываются до аудита: повтор не добавляет работы и оплачиваться не должен. Максимум — 20 адресов.
     * @param array{strategy?: 'mobile'|'desktop', timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - strategy — Профиль устройства, единый для всего пакета.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - results (list<array<string, mixed>>) — Результаты по каждому адресу — в том же порядке, в каком адреса пришли в запросе.
     *     - total (int) — Сколько адресов было в пакете (после удаления пустых строк и дубликатов).
     *     - succeededCount (int) — Сколько адресов удалось проверить.
     *     - failedCount (int) — Сколько адресов проверить не удалось.
     *     - elapsedMs (int) — Общее время обработки пакета в миллисекундах.
     *
     * @throws AtloriumException
     */
    public function auditBatch($urls, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->auditBatch($urls, $params)->getData();

        return $data;
    }
}
