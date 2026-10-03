<?php

/**
 * Ручная проверка SDK против живого API: по одному запросу на каждый из 23 сервисов.
 *
 * По умолчанию — только на публичном демо-ключе (ответы-моки, деньги не списываются). Лимиты
 * демо-режима равны лимитам пользователя, поэтому скрипт не повторяет запросы и останавливается
 * на первом 429. В CI по пушу не запускается.
 *
 * Запуск:
 *   php scripts/live_smoke.php
 *   php scripts/live_smoke.php --allow-live-key   # с ATLORIUM_API_KEY — ТРАТИТ КРЕДИТЫ
 */

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

use Atlorium\Client;
use Atlorium\Exception\AtloriumException;
use Atlorium\Exception\RateLimitException;

$png = __DIR__ . '/../tests/fixtures/white.png';

/** @var array<string, callable(Client): \Atlorium\ApiResponse> $checks */
$checks = [
    'egrul.get' => static fn (Client $c) => $c->egrul->withRawResponse()->get('7707083893'),
    'cbr.get' => static fn (Client $c) => $c->cbr->withRawResponse()->get('044525225'),
    'swift.get' => static fn (Client $c) => $c->swift->withRawResponse()->get('SABRRUMM'),
    'bin.lookup' => static fn (Client $c) => $c->bin->withRawResponse()->lookup('424242'),
    'aml.screen' => static fn (Client $c) => $c->aml->withRawResponse()->screen('TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE', 'TRX'),
    'ipinfo.lookup' => static fn (Client $c) => $c->ipinfo->withRawResponse()->lookup('8.8.8.8'),
    'gar.search' => static fn (Client $c) => $c->gar->withRawResponse()->search('Москва Тверская', ['limit' => 1]),
    'addressstd.standardize' => static fn (Client $c) => $c->addressstd->withRawResponse()->standardize('Москва, Тверская 1'),
    'phone.validate' => static fn (Client $c) => $c->phone->withRawResponse()->validate('+79161234567'),
    'email.validate' => static fn (Client $c) => $c->email->withRawResponse()->validate('test@example.com'),
    'ocr.recognize' => static fn (Client $c) => $c->ocr->withRawResponse()->recognize($png),
    'dns.lookup' => static fn (Client $c) => $c->dns->withRawResponse()->lookup('atlorium.com'),
    'cidr.calculate' => static fn (Client $c) => $c->cidr->withRawResponse()->calculate('192.168.1.10', 24),
    'cron.evaluate' => static fn (Client $c) => $c->cron->withRawResponse()->evaluate('*/15 * * * *', ['take' => 3]),
    'certificate.check' => static fn (Client $c) => $c->certificate->withRawResponse()->check('atlorium.com'),
    'weather.get' => static fn (Client $c) => $c->weather->withRawResponse()->get(55.7558, 37.6173),
    'aichat.send' => static fn (Client $c) => $c->aichat->withRawResponse()->send('Привет'),
    'geocodeforward.search' => static fn (Client $c) => $c->geocodeforward->withRawResponse()->search('Москва, Тверская 1', ['size' => 1]),
    'geocodereverse.lookup' => static fn (Client $c) => $c->geocodereverse->withRawResponse()->lookup(55.7558, 37.6173),
    'testdata.generate' => static fn (Client $c) => $c->testdata->withRawResponse()->generate(['count' => 2, 'seed' => 42]),
    'urlcheck.check' => static fn (Client $c) => $c->urlcheck->withRawResponse()->check('https://example.com'),
    'pagespeed.audit' => static fn (Client $c) => $c->pagespeed->withRawResponse()->audit('https://atlorium.com'),
    'imagecheck.analyze' => static fn (Client $c) => $c->imagecheck->withRawResponse()->analyze($png),
];

$client = new Client(null, ['maxRetries' => 0]);
if (!$client->isSandbox() && !in_array('--allow-live-key', $argv, true)) {
    echo "Задан боевой ATLORIUM_API_KEY. Запуск с ним тратит кредиты — добавьте --allow-live-key.\n";
    exit(2);
}

$failures = 0;
foreach ($checks as $name => $check) {
    try {
        $raw = $check($client);
        printf("OK   %s: HTTP %d, sandbox=%s, %s\n", $name, $raw->getStatusCode(), $raw->isSandbox() ? 'true' : 'false', gettype($raw->getData()));
    } catch (RateLimitException $error) {
        printf("STOP %s: 429, повторить через %s с. Дальше не идём.\n", $name, (string) $error->getRetryAfter());
        exit(3);
    } catch (AtloriumException $error) {
        ++$failures;
        printf("FAIL %s: %s\n", $name, $error->getMessage());
    }
}

printf("\nИтого: %d из %d успешно.\n", count($checks) - $failures, count($checks));
exit($failures > 0 ? 1 : 0);
