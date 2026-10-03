<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;
use Atlorium\ImageInput;

/**
 * API анализа изображения: $client->imagecheck.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/image-moderation-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class ImageCheckResource extends AbstractResource
{
    private ImageCheckRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new ImageCheckRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): ImageCheckRawResource
    {
        return $this->raw;
    }

    /**
     * Анализирует изображение и возвращает решение модерации вместе с разбором содержимого.
     *
     * Пример запроса:
     *
     * POST /api/imagecheck
     * {
     *   "image": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...",
     *   "includeText": true,
     *   "includeWebSearch": false
     * }
     *
     * ФОРМАТ ИЗОБРАЖЕНИЯ. Base64 — «голым» или в виде data-URL. Предел 7 МБ в декодированном
     * виде. Принимаются JPEG, PNG, WEBP, BMP, GIF. PDF и видео на вход не подаются.
     *
     * КАК ЧИТАТЬ ОТВЕТ. Сначала decision — что делать. Затем flags — почему принято такое
     * решение: у каждого срабатывания есть категория, слой-источник и конкретное
     * доказательство (найденная метка, найденное в тексте выражение или категория
     * классификации с её степенью). И только потом сырые слои, если решение нужно
     * перепроверить или оспорить. Сырые слои отдаются намеренно: в модерации ошибка стоит
     * дорого в обе стороны, и решение, которое нельзя проверить, доверия не заслуживает.
     *
     * ПУСТОЙ text — НЕ ОШИБКА. Он означает либо что читаемого текста на картинке нет, либо
     * что слой не заказывался. Какой это случай, видно по billedUnits.
     *
     * ПАКЕТНОГО РЕЖИМА НЕТ намеренно: изображение — это мегабайты в теле запроса, и пакет
     * превратился бы в стомегабайтный POST с таймаутами на промежуточных узлах. Поток
     * модерируется параллельными одиночными запросами — они независимы, повторяются
     * поштучно и не теряют весь пакет из-за одной битой картинки.
     *
     * ТАРИФИКАЦИЯ. От одной до трёх единиц, см. описание контроллера. Анализ, который
     * не выполнен, не тарифицируется вовсе.
     *
     * @param ImageInput|\SplFileInfo|resource|string $image Изображение в кодировке Base64 — «голым» base64 или data-URL вида data:image/jpeg;base64,.... Обязательное поле. Предел — 7 МБ в декодированном виде; фотография с телефона укладывается с запасом. Число нецелое не случайно: картинка уезжает в base64 внутри JSON-запроса, у которого чужой потолок 10 МБ, а кодирование добавляет около 37 %. Принимаются растровые форматы (JPEG, PNG, WEBP, BMP, GIF); PDF и видео на вход не подаются.
     * @param array{includeText?: bool, includeWebSearch?: bool, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - includeText — Читать текст НА изображении и сверять его со словарём запрещённых выражений.
     *     - includeWebSearch — Добавить поиск изображения в вебе: где ещё встречается эта картинка и какие страницы её содержат.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - decision ('allow'|'review'|'block') — Сводное решение: публиковать, показать модератору или не публиковать.
     *     - flags (list<array<string, mixed>>) — Все сработавшие фильтры, самые строгие первыми.
     *     - safeSearch (array<string, mixed>) — Слой 1: классификация содержимого по пяти категориям.
     *     - labels (list<array<string, mixed>>) — Слой 2: что изображено.
     *     - text (string) — Слой 3: текст, найденный НА изображении, одной строкой с сохранением переносов.
     *     - webSearch (array<string, mixed>|null) — Результат поиска в вебе.
     *     - billedUnits (int) — Сколько единиц работы фактически списано за этот запрос — от одной до трёх.
     *     - elapsedMs (int) — Время обработки на нашей стороне, миллисекунды.
     *     - message (string) — Пояснение к результату, когда оно есть: например, что часть слоёв не отработала и решение принято по остальным.
     *
     * @throws AtloriumException
     */
    public function analyze($image, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->analyze($image, $params)->getData();

        return $data;
    }
}
