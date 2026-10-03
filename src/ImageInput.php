<?php

declare(strict_types=1);

namespace Atlorium;

/**
 * Изображение для OCR и модерации.
 *
 * API ждёт картинку строкой Base64 внутри JSON. SDK кодирует её сам — методы принимают то, что есть
 * под рукой:
 *
 *     $client->ocr->recognize('/path/to/scan.png');               // путь к файлу
 *     $client->ocr->recognize(file_get_contents($path));          // байты
 *     $client->ocr->recognize(fopen($path, 'rb'));                // поток
 *     $client->ocr->recognize(new \SplFileInfo($path));
 *     $client->ocr->recognize(ImageInput::fromBase64($base64));   // уже готовый Base64 или data-URL
 *
 * Строка распознаётся так: существующий файл → путь; data-URL или корректный Base64 → как есть;
 * строка, похожая на путь к картинке, но файла нет → исключение; иначе — байты изображения.
 * Чтобы не полагаться на угадывание, используйте фабрики fromPath/fromBytes/fromBase64.
 */
final class ImageInput
{
    private const MAX_PATH_LENGTH = 4096;

    private string $base64;

    private function __construct(string $base64)
    {
        $this->base64 = $base64;
    }

    public static function fromPath(string $path): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('Файл изображения не найден или недоступен для чтения: %s', $path));
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \InvalidArgumentException(sprintf('Не удалось прочитать файл изображения: %s', $path));
        }

        return new self(base64_encode($content));
    }

    /** Байты изображения, например результат file_get_contents(). */
    public static function fromBytes(string $bytes): self
    {
        if ($bytes === '') {
            throw new \InvalidArgumentException('Пустое изображение.');
        }

        return new self(base64_encode($bytes));
    }

    /** Уже закодированное изображение: Base64 или data-URL вида data:image/png;base64,.... */
    public static function fromBase64(string $base64): self
    {
        if (trim($base64) === '') {
            throw new \InvalidArgumentException('Пустое изображение.');
        }

        return new self($base64);
    }

    /**
     * Изображение из потока.
     *
     * @param mixed $stream открытый на чтение поток (resource), например fopen($path, 'rb')
     */
    public static function fromStream($stream): self
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Ожидается открытый поток (resource).');
        }
        $content = stream_get_contents($stream);
        if ($content === false) {
            throw new \InvalidArgumentException('Не удалось прочитать поток изображения.');
        }

        return self::fromBytes($content);
    }

    /**
     * Превращает изображение из любого поддерживаемого вида в строку Base64.
     *
     * @param mixed $image ImageInput, путь или байты (string), resource, \SplFileInfo
     */
    public static function encode($image): string
    {
        if ($image instanceof self) {
            return $image->base64;
        }
        if ($image instanceof \SplFileInfo) {
            return self::fromPath($image->getPathname())->base64;
        }
        if (is_resource($image)) {
            return self::fromStream($image)->base64;
        }
        if (is_string($image)) {
            return self::fromString($image)->base64;
        }

        throw new \InvalidArgumentException(
            'Неподдерживаемый тип изображения: ' . gettype($image) . '. Ожидается путь, байты, поток, \SplFileInfo или ImageInput.'
        );
    }

    public function toBase64(): string
    {
        return $this->base64;
    }

    private static function fromString(string $value): self
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Пустое изображение.');
        }
        if (strncmp($value, 'data:', 5) === 0) {
            return new self($value);
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            throw new \InvalidArgumentException('API не принимает ссылку на изображение: скачайте его и передайте байты.');
        }

        $pathLike = strlen($value) <= self::MAX_PATH_LENGTH && strpbrk($value, "\0\r\n") === false;
        // @ — на хостингах с open_basedir is_file() на чужой путь выдаёт предупреждение.
        if ($pathLike && @is_file($value)) {
            return self::fromPath($value);
        }
        // Base64 с переносами строк (chunk_split, MIME) — тоже Base64, а не байты: переносы убираем.
        $compact = preg_replace('~\s+~', '', $value);
        if (is_string($compact) && strlen($compact) >= 16 && strlen($compact) % 4 === 0 && self::isBase64($compact)) {
            return new self($compact);
        }
        if ($pathLike && preg_match('~([/\\\\]|\.(png|jpe?g|gif|webp|bmp|tiff?|heic|pdf)$)~i', $value) === 1) {
            throw new \InvalidArgumentException(sprintf('Файл изображения не найден: %s', $value));
        }

        return self::fromBytes($value);
    }

    private static function isBase64(string $value): bool
    {
        return preg_match('~^[A-Za-z0-9+/]+={0,2}$~', $value) === 1;
    }
}
