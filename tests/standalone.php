<?php

/**
 * Проверка работы SDK БЕЗ Composer — так, как его подключают в 1С-Битрикс: только autoload.php.
 *
 * Запуск: php tests/standalone.php (в CI — отдельным шагом, вне PHPUnit).
 */

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

use Atlorium\Client;
use Atlorium\Enum\CronTemplateType;
use Atlorium\Http\MockTransport;
use Atlorium\Http\Response;

function fail(string $message): void
{
    fwrite(STDERR, "standalone: FAIL — {$message}\n");
    exit(1);
}

if (class_exists('Composer\\Autoload\\ClassLoader', false)) {
    fail('загружен автозагрузчик Composer, проверка не имеет смысла');
}

$transport = new MockTransport(Response::json(200, ['found' => true, 'inn' => '7707083893']));
$client = new Client(null, ['transport' => $transport]);
$card = $client->egrul->get('7707083893');

if (($card['inn'] ?? null) !== '7707083893') {
    fail('неожиданный ответ');
}
$request = $transport->getLastRequest();
if ($request === null || $request->getPath() !== '/api/egrul/7707083893') {
    fail('неожиданный запрос');
}
if (CronTemplateType::WEEKLY !== 5) {
    fail('не загрузился Atlorium\\Enum\\CronTemplateType');
}

// Все классы пакета должны находиться автозагрузчиком.
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src', FilesystemIterator::SKIP_DOTS));
$count = 0;
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $relative = substr($file->getPathname(), strlen(__DIR__ . '/../src/'));
    $class = 'Atlorium\\' . str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
    if ($class === 'Atlorium\\Http\\Psr18Transport') {
        continue; // требует пакетов psr/http-*, которых без Composer нет — это ожидаемо
    }
    if (!class_exists($class) && !interface_exists($class)) {
        fail("класс {$class} не найден автозагрузчиком");
    }
    ++$count;
}

echo "standalone: ok ({$count} классов)\n";
