<?php

declare(strict_types=1);

namespace Atlorium\Tests;

use Atlorium\ImageInput;
use PHPUnit\Framework\TestCase;

final class ImageInputTest extends TestCase
{
    private const PNG = __DIR__ . '/fixtures/white.png';

    private static function expected(): string
    {
        return base64_encode((string) file_get_contents(self::PNG));
    }

    public function testPathBytesFileInfoAndStream(): void
    {
        self::assertSame(self::expected(), ImageInput::encode(self::PNG));
        self::assertSame(self::expected(), ImageInput::encode((string) file_get_contents(self::PNG)));
        self::assertSame(self::expected(), ImageInput::encode(new \SplFileInfo(self::PNG)));

        $stream = fopen(self::PNG, 'rb');
        self::assertIsResource($stream);
        self::assertSame(self::expected(), ImageInput::encode($stream));
        fclose($stream);
    }

    public function testFactories(): void
    {
        self::assertSame(self::expected(), ImageInput::fromPath(self::PNG)->toBase64());
        self::assertSame(self::expected(), ImageInput::fromBytes((string) file_get_contents(self::PNG))->toBase64());
        self::assertSame('AAAA', ImageInput::fromBase64('AAAA')->toBase64());
        $input = ImageInput::fromPath(self::PNG);
        self::assertSame(self::expected(), ImageInput::encode($input));
    }

    public function testBase64AndDataUrlArePassedAsIs(): void
    {
        self::assertSame(self::expected(), ImageInput::encode(self::expected()));
        self::assertSame('data:image/png;base64,AAAA', ImageInput::encode('data:image/png;base64,AAAA'));
    }

    public function testBase64WithLineBreaksIsNotEncodedTwice(): void
    {
        self::assertSame(self::expected(), ImageInput::encode(chunk_split(self::expected(), 76, "\r\n")));
    }

    public function testShortTextIsTreatedAsBytes(): void
    {
        self::assertSame(base64_encode('img'), ImageInput::encode('img'));
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function invalidImages(): iterable
    {
        yield 'нет файла' => ['/tmp/atlorium/нет-такого-файла.jpg', 'не найден'];
        yield 'пустая строка' => ['', 'Пустое'];
        yield 'ссылка' => ['https://example.com/a.png', 'ссылку'];
        yield 'число' => [42, 'Неподдерживаемый'];
    }

    /**
     * @dataProvider invalidImages
     *
     * @param mixed $image
     */
    public function testInvalidImages($image, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        ImageInput::encode($image);
    }
}
