<?php

/**
 * Автозагрузчик SDK Atlorium для проектов БЕЗ Composer (например, 1С-Битрикс).
 *
 * Подключение — одна строка:
 *
 *     require_once '/путь/к/atlorium-php/autoload.php';
 *     $client = new \Atlorium\Client();
 *
 * Если в проекте есть Composer, этот файл не нужен: `composer require atlorium/atlorium`
 * и обычный `vendor/autoload.php`.
 *
 * Синтаксис файла намеренно консервативный: на слишком старом PHP он должен выдать
 * понятное предупреждение, а не синтаксическую ошибку.
 */

if (defined('ATLORIUM_SDK_AUTOLOAD_REGISTERED')) {
    return;
}
define('ATLORIUM_SDK_AUTOLOAD_REGISTERED', true);

// Анализатор кода, настроенный на PHP 7.4, считает условие всегда ложным; оно нужно на более старом PHP.
// @phpstan-ignore-next-line
if (PHP_VERSION_ID < 70400) {
    trigger_error(
        'Atlorium SDK требует PHP 7.4 или новее, сейчас ' . PHP_VERSION . '. Автозагрузчик не подключён.',
        E_USER_WARNING
    );

    return;
}

spl_autoload_register(static function (string $class) {
    $prefix = 'Atlorium\\';
    $prefixLength = 9;

    if (strncmp($class, $prefix, $prefixLength) !== 0) {
        return;
    }

    $file = __DIR__ . '/src/' . str_replace('\\', '/', (string) substr($class, $prefixLength)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
