<?php

declare(strict_types=1);

namespace Atlorium\Resource;

use Atlorium\ApiResponse;
use Atlorium\BaseClient;
use Atlorium\Exception\AtloriumException;
use Atlorium\ImageInput;

/**
 * API распознавания текста с изображения: $client->ocr.
 *
 * Примеры на шести языках: https://github.com/atlorium-api/image-ocr-api-client
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class OcrResource extends AbstractResource
{
    private OcrRawResource $raw;

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);
        $this->raw = new OcrRawResource($client);
    }

    /**
     * Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками.
     */
    public function withRawResponse(): OcrRawResource
    {
        return $this->raw;
    }

    /**
     * Распознаёт содержимое страницы на изображении. Изображение передаётся в Base64
     * (можно как data-URL), предел — 10 МБ в декодированном виде.
     *
     * Пример запроса:
     * POST /api/ocr/image-to-text
     * { "image": "iVBORw0KGgoAAAANSUhEUgAA..." }
     *
     * @param ImageInput|\SplFileInfo|resource|string $image Изображение в кодировке Base64 (можно как «голый» base64, так и data-URL вида "data:image/png;base64,...."). Обязательное поле. Предел — 10 МБ в декодированном виде: скан листа A4 в 300 dpi укладывается с запасом. PDF на вход не принимается — многостраничный файл нужно отрендерить в изображения и отправлять постранично.
     * @param array{mode?: string, timeout?: float|int, maxRetries?: int, headers?: array<string, string>} $params
     *     Необязательные параметры:
     *     - mode — Режим распознавания — одно из значений OcrModes.
     *     Настройки вызова: timeout (секунды), maxRetries, headers.
     *
     * @return array<string, mixed>
     *     Поля ответа:
     *     - recognized (bool) — true, если изображение распознано и в text лежит его содержимое.
     *     - text (string|null) — Содержимое изображения (при recognized = true); иначе null.
     *     - format (string) — Формат поля text — одно из значений OcrTextFormats.
     *     - mode (string) — Режим, в котором изображение РЕАЛЬНО распознано.
     *     - units (int) — Единицы работы, в которые обошёлся запрос, — по ним он и тарифицирован.
     *     - truncated (bool) — true, если ответ ОБОРВАН и содержимое распознано НЕ ПОЛНОСТЬЮ: упёрлись в предел длины ответа или в потолок числа областей на странице.
     *     - elapsedMs (int) — Время обработки запроса в миллисекундах — включая ожидание в очереди, а не только само распознавание.
     *
     * @throws AtloriumException
     */
    public function recognize($image, array $params = []): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->raw->recognize($image, $params)->getData();

        return $data;
    }
}
