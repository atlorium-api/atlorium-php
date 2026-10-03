<?php

declare(strict_types=1);

// Маршрутизатор встроенного сервера PHP для tests/CurlTransportTest.php.
// Сервер слушает только 127.0.0.1; во внешнюю сеть тесты не ходят.

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);

if ($path === '/echo') {
    $headers = [];
    foreach (getallheaders() as $name => $value) {
        $headers[strtolower((string) $name)] = $value;
    }
    header('Content-Type: application/json; charset=utf-8');
    header('X-Test: yes');
    echo json_encode([
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'path' => $path,
        'query' => $_GET,
        'headers' => $headers,
        'body' => (string) file_get_contents('php://input'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} elseif ($path === '/binary') {
    header('Content-Type: application/pdf');
    echo "%PDF-1.7\n\x00\x01\xFF\xFE";
} elseif ($path === '/error') {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Test: yes');
    echo json_encode(
        ['error' => 'not_found', 'title' => 'Не найдено', 'message' => 'Нет такой организации'],
        JSON_UNESCAPED_UNICODE
    );
} elseif ($path === '/sleep') {
    // Дольше таймаута клиента в тесте: проверяется таймаут чтения.
    usleep(2000000);
    header('Content-Type: text/plain');
    echo 'late';
} else {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'unknown route';
}
