# atlorium/atlorium — official PHP SDK

[![Packagist](https://img.shields.io/packagist/v/atlorium/atlorium.svg)](https://packagist.org/packages/atlorium/atlorium)
[![PHP](https://img.shields.io/packagist/php-v/atlorium/atlorium.svg)](https://packagist.org/packages/atlorium/atlorium)
[![CI](https://github.com/atlorium-api/atlorium-php/actions/workflows/ci.yml/badge.svg)](https://github.com/atlorium-api/atlorium-php/actions/workflows/ci.yml)

Russian company registry (EGRUL), bank BIK and SWIFT lookup, FIAS/GAR addresses, geocoding, OCR, DNS, SSL,
weather, AI chat and more B2B APIs for the Russian market — **all 23 [Atlorium](https://atlorium.com) services
in one package**.

- **All 59 API methods**, parameters and response fields documented in PHPDoc.
- **PHP 7.4+, zero dependencies**: only `ext-curl` and `ext-json`.
- **Works without Composer** — a single `require`, ready for **1C-Bitrix**, WordPress and legacy projects.
- **Works instantly, no sign-up** — on a public sandbox key.
- Retries on 429/503 honouring `Retry-After`, typed exceptions (messages in Russian), optional PSR-18 client.

Русская версия: [README.md](https://github.com/atlorium-api/atlorium-php/blob/main/README.md)

## Install

With Composer:

```bash
composer require atlorium/atlorium
```

Without Composer: download `atlorium-php-<version>.zip` from
[Releases](https://github.com/atlorium-api/atlorium-php/releases) and unpack it — it contains a single
`atlorium-php/` folder. Include the autoloader:

```php
require_once '/path/to/atlorium-php/autoload.php';
```

## Quick start

```php
$client = new \Atlorium\Client(); // no key — sandbox mode

$card = $client->egrul->get('7707083893');  // company by INN / OGRN
echo $card['shortName'], ' — ', $card['status'];

$bank = $client->cbr->get('044525225');      // bank by BIK
echo $bank['name'], ' ', $bank['corrAccount'];
```

## Sandbox mode

Without a key the client uses the **public sandbox key** `ak_sandbox_demo_mockdata_v1`. The API returns
realistic but generated data (mocks), so you can build and test an integration before paying. Responses are
**deterministic** — the same request always gives the same result, which makes stable tests easy. Timestamp
fields are the only exception.

Request rate limits in sandbox mode are the same as for a registered user; see
[atlorium.com/pricing](https://atlorium.com/pricing).

If the key is set neither as an argument nor via the `ATLORIUM_API_KEY` environment variable, the SDK writes a
one-time notice to `error_log()`, so a key forgotten in production never turns into silent mocks. To choose
sandbox mode explicitly and without the notice, pass the sandbox key:
`new \Atlorium\Client(\Atlorium\Client::SANDBOX_API_KEY)`.

## Production key

Get a key at [atlorium.com](https://atlorium.com) and pass it in one of two ways:

```php
$client = new \Atlorium\Client('ak_...'); // explicitly
// or the ATLORIUM_API_KEY environment variable — no code changes
```

`$client->isSandbox()` tells which mode is active. The key is hidden from `var_dump`/`print_r`, never appears in
exception messages, and on PHP 8.2+ is also hidden from stack traces (`#[\SensitiveParameter]`).

## Services

| Service | Resource | Methods | Examples in 6 languages |
|---|---|---|---|
| Russian company registry (EGRUL/EGRIP) | `$client->egrul` | `get`, `search`, `getExcerpt` | [egrul-api-client](https://github.com/atlorium-api/egrul-api-client) |
| Bank BIK directory (Bank of Russia) | `$client->cbr` | `get`, `search`, `getStats` | [cbr-bik-api-client](https://github.com/atlorium-api/cbr-bik-api-client) |
| SWIFT/BIC | `$client->swift` | `get`, `search`, `validate`, `getStats` | [swift-bic-api-client](https://github.com/atlorium-api/swift-bic-api-client) |
| Card BIN lookup | `$client->bin` | `lookup`, `validate` | [bin-lookup-api-client](https://github.com/atlorium-api/bin-lookup-api-client) |
| Crypto wallet AML screening | `$client->aml` | `screen`, `validate` | [aml-crypto-screening-api-client](https://github.com/atlorium-api/aml-crypto-screening-api-client) |
| IP profile | `$client->ipinfo` | `lookup` | [ip-geolocation-api-client](https://github.com/atlorium-api/ip-geolocation-api-client) |
| FIAS/GAR addresses | `$client->gar` | `search`, `suggest`, `getObject`, `getObjectById`, `getChildren`, `getHierarchy`, `getStats`, `listRegions`, `getRegionStats` | [gar-fias-address-api-client](https://github.com/atlorium-api/gar-fias-address-api-client) |
| Address standardization | `$client->addressstd` | `standardize` | [address-standardization-api-client](https://github.com/atlorium-api/address-standardization-api-client) |
| Phone validation | `$client->phone` | `validate` | [phone-validation-api-client](https://github.com/atlorium-api/phone-validation-api-client) |
| E-mail verification | `$client->email` | `validate` | [email-verification-api-client](https://github.com/atlorium-api/email-verification-api-client) |
| Text recognition (OCR) | `$client->ocr` | `recognize` | [image-ocr-api-client](https://github.com/atlorium-api/image-ocr-api-client) |
| DNS | `$client->dns` | `lookup`, `checkPropagation` | [dns-lookup-api-client](https://github.com/atlorium-api/dns-lookup-api-client) |
| CIDR calculator | `$client->cidr` | `calculate`, `split`, `check`, `supernet` | [cidr-subnet-calculator-api-client](https://github.com/atlorium-api/cidr-subnet-calculator-api-client) |
| Cron expressions | `$client->cron` | `evaluate`, `build` | [cron-expression-parser-api-client](https://github.com/atlorium-api/cron-expression-parser-api-client) |
| SSL certificate | `$client->certificate` | `check`, `checkUrl`, `checkBatch` | [ssl-certificate-check-api-client](https://github.com/atlorium-api/ssl-certificate-check-api-client) |
| Weather | `$client->weather` | `get` | [weather-api-client](https://github.com/atlorium-api/weather-api-client) |
| AI chat | `$client->aichat` | `send`, `getSession`, `deleteSession`, `listModels` | [ai-chat-api-client](https://github.com/atlorium-api/ai-chat-api-client) |
| Forward geocoding | `$client->geocodeforward` | `search`, `searchStructured`, `searchBatch`, `getPlace`, `searchPostcode` | [geocoding-api-client](https://github.com/atlorium-api/geocoding-api-client) |
| Reverse geocoding | `$client->geocodereverse` | `lookup`, `nearby` | [reverse-geocoding-api-client](https://github.com/atlorium-api/reverse-geocoding-api-client) |
| Russian test data generator | `$client->testdata` | `generate`, `generateWithBody`, `listFields` | [test-data-generator-api-client](https://github.com/atlorium-api/test-data-generator-api-client) |
| URL reputation | `$client->urlcheck` | `check`, `checkBatch` | [url-reputation-api-client](https://github.com/atlorium-api/url-reputation-api-client) |
| Site audit (Core Web Vitals) | `$client->pagespeed` | `audit`, `auditBatch` | [core-web-vitals-api-client](https://github.com/atlorium-api/core-web-vitals-api-client) |
| Image moderation | `$client->imagecheck` | `analyze` | [image-moderation-api-client](https://github.com/atlorium-api/image-moderation-api-client) |

Required arguments are positional; optional parameters go into the last array. Responses are plain
associative arrays; every field is listed in the method's PHPDoc (in Russian), so your IDE shows them as you
type. A typo in a parameter name throws `InvalidArgumentException` instead of being silently ignored.

## Examples

One example for every service:

```php
use Atlorium\Client;
use Atlorium\Enum\CronTemplateType;
use Atlorium\Enum\DayOfWeek;

$client = new Client();

// Companies and banks
$client->egrul->search('Сбербанк', ['limit' => 5]);
file_put_contents('excerpt.pdf', $client->egrul->getExcerpt('7707083893')); // official registry excerpt, PDF
$client->cbr->search('Тинькофф');
$client->swift->validate('SABRRUMMXXX');
$client->bin->lookup('424242');
$client->aml->screen('TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE', 'TRX');

// Addresses and maps
$client->gar->suggest('Москва Тверская');
$client->addressstd->standardize('мск тверская 1', ['geocode' => true]);
$client->geocodeforward->search('Kazan, Baumana 1', ['size' => 1]);
$client->geocodereverse->lookup(55.7558, 37.6173, ['includeAdmin' => true]);
$client->weather->get(55.7558, 37.6173);

// Contacts
$client->phone->validate('+79161234567');
$client->email->validate('info@example.com');
$client->ipinfo->lookup('8.8.8.8');

// Images: a path, bytes, a stream or \SplFileInfo — the SDK encodes Base64 itself
$client->ocr->recognize('/upload/scan.png');
$client->imagecheck->analyze(file_get_contents('/upload/photo.jpg'), ['includeWebSearch' => true]);

// Websites and infrastructure
$client->dns->lookup('atlorium.com');
$client->dns->checkPropagation('atlorium.com', ['resolvers' => ['1.1.1.1', '8.8.8.8']]);
$client->certificate->check('atlorium.com');
$client->urlcheck->checkBatch(['https://example.com', 'https://example.org']);
$client->pagespeed->audit('https://atlorium.com', ['strategy' => 'mobile']);
$client->cidr->calculate('192.168.1.10', 24);
$client->cron->evaluate('*/15 * * * *', ['timeZoneId' => 'Europe/Moscow', 'take' => 3]);
$client->cron->build(['template' => CronTemplateType::WEEKLY, 'timeOfDay' => '09:30:00', 'weekDays' => [DayOfWeek::MONDAY]]);

// AI chat and test data
$reply = $client->aichat->send('Draft a reconciliation letter to a counterparty');
$client->aichat->send('Shorter, please', ['sessionId' => $reply['sessionId']]);
$client->aichat->deleteSession($reply['sessionId']); // confirmation string
$client->testdata->generate(['count' => 10, 'fields' => ['fullName', 'innPerson', 'snils'], 'seed' => 42]);
$csv = $client->testdata->generate(['count' => 10, 'format' => 'csv']); // CSV string
```

## Images

`ocr->recognize()` and `imagecheck->analyze()` accept an image in any convenient form; the SDK encodes it to
Base64 itself:

- `\Atlorium\ImageInput` — an explicitly created object (see below);
- `\SplFileInfo` — a file;
- a stream (`resource`), e.g. `fopen($path, 'rb')`;
- a string — the SDK decides what it is using these rules, in order:
  1. starts with `data:` — a data URL, sent as is;
  2. starts with `http://` or `https://` — `InvalidArgumentException`: the API does not accept links,
     download the image and pass its bytes;
  3. a path to an existing file — the file is read;
  4. valid Base64 (after removing spaces and line breaks — at least 16 characters, length divisible by 4) —
     sent as is;
  5. looks like a path to an image (contains `/` or `\`, or ends with `.png`, `.jpg`, `.pdf` and so on) but the
     file does not exist — `InvalidArgumentException` "image file not found";
  6. otherwise — raw image bytes (e.g. the result of `file_get_contents()`).

To avoid guessing, use the factories:

```php
use Atlorium\ImageInput;

$client->ocr->recognize(ImageInput::fromPath('/upload/scan.png'));
$client->ocr->recognize(ImageInput::fromBytes($bytes));
$client->ocr->recognize(ImageInput::fromStream(fopen('/upload/scan.png', 'rb')));
$client->ocr->recognize(ImageInput::fromBase64($base64OrDataUrl));
```

## 1C-Bitrix

The SDK needs no Composer and does not clash with the Bitrix core: every class lives in the `Atlorium\`
namespace.

**1. Installation.** Unpack the release archive `atlorium-php-<version>.zip` into `local/php_interface/lib/` —
you get `local/php_interface/lib/atlorium-php/`. Add to `local/php_interface/init.php`:

```php
require_once __DIR__ . '/lib/atlorium-php/autoload.php';

/** One client per page request. */
function atlorium(): \Atlorium\Client
{
    static $client = null;
    if ($client === null) {
        $settings = \Bitrix\Main\Config\Configuration::getValue('atlorium') ?: [];
        $client = new \Atlorium\Client($settings['apiKey'] ?? null);
    }

    return $client;
}
```

**2. Keep the key out of the code** — in `bitrix/.settings_extra.php` (this file is not touched by updates and
code backups):

```php
<?php
return [
    'atlorium' => ['value' => ['apiKey' => 'ak_...'], 'readonly' => true],
];
```

**3. Cache responses.** Every API call is billed, and EGRUL or BIK data does not change every minute:

```php
use Bitrix\Main\Data\Cache;

function companyByInn(string $inn): array
{
    $cache = Cache::createInstance();
    if ($cache->initCache(86400, 'egrul_' . $inn, '/atlorium/egrul')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    try {
        $card = atlorium()->egrul->get($inn);
    } catch (\Atlorium\Exception\AtloriumException $error) {
        $cache->abortDataCache();
        throw $error;
    }
    $cache->endDataCache($card);

    return $card;
}
```

**4. Sites in windows-1251.** The API accepts and returns UTF-8. Convert strings on the way in and out:

```php
use Bitrix\Main\Text\Encoding;

$query = Encoding::convertEncoding($_GET['q'], SITE_CHARSET, 'UTF-8');
$result = atlorium()->egrul->search($query);
$result = Encoding::convertEncoding($result, 'UTF-8', SITE_CHARSET);
```

If you forget to convert, the SDK throws `InvalidArgumentException` with a hint instead of sending a broken
request.

**5. Proxies and outdated certificates on shared hosting** — via cURL options:

```php
$client = new \Atlorium\Client($key, [
    'transport' => new \Atlorium\Http\CurlTransport([
        CURLOPT_PROXY => 'http://proxy.local:3128',
        // CURLOPT_CAINFO => '/path/to/cacert.pem',
    ]),
]);
```

**6. Long operations belong in Bitrix agents or queues**, not in a page handler: the site audit and the AI chat
may take minutes to answer (see "Retries and timeouts").

## Errors

All exceptions extend `Atlorium\Exception\AtloriumException`:

| HTTP | Exception | When |
|---|---|---|
| 400 | `ValidationException` | invalid input |
| 401 | `AuthenticationException` | the key is missing, expired or invalid |
| 402 | `InsufficientCreditsException` | not enough credits (`getBalance()` — current balance) |
| 403 | `PermissionDeniedException` | the service is not included in your account terms (code `service_not_in_account_terms`); to enable it, write to support@atlorium.com |
| 404 | `NotFoundException` | object not found |
| 429 | `RateLimitException` | rate limit exceeded (`getRetryAfter()` — seconds to wait) |
| 503 | `ServiceUnavailableException` | the service is temporarily unavailable: an upstream data source failed or planned maintenance is in progress (codes `service_disabled`, `maintenance`; status — https://atlorium.com/status). **You are not charged for such a response** |
| other 5xx | `ServerException` | a server-side error, you are not charged |
| — | `ConnectionException`, `TimeoutException` | network, timeout |

Hierarchy:

```
AtloriumException
├── ValidationException             400
├── AuthenticationException         401
├── InsufficientCreditsException    402
├── PermissionDeniedException       403
├── NotFoundException               404
├── RateLimitException              429
├── ServerException                 5xx
│   └── ServiceUnavailableException 503
└── ConnectionException             no response (network)
    └── TimeoutException            timeout expired
```

`catch (ServerException $e)` catches any 5xx error, 503 included. Other codes (405, 415 and so on) are a plain
`AtloriumException`.

```php
use Atlorium\Exception\AtloriumException;
use Atlorium\Exception\NotFoundException;
use Atlorium\Exception\RateLimitException;
use Atlorium\Exception\ServiceUnavailableException;

try {
    $card = $client->egrul->get($inn);
} catch (NotFoundException $e) {
    // not in the registry
} catch (RateLimitException $e) {
    echo 'Retry in ', $e->getRetryAfter(), ' s';
} catch (ServiceUnavailableException $e) {
    echo $e->isMaintenance() ? 'Planned maintenance, see https://atlorium.com/status' : 'Data source temporarily unavailable';
} catch (AtloriumException $e) {
    echo $e->getStatusCode(), ' ', $e->getErrorCode(), ': ', $e->getMessage();
}
```

`getErrorCode()` is a stable machine-readable code (`not_found`, `insufficient_credits`, `rate_limited_client`,
`service_not_in_account_terms`, `maintenance` …) — branch on it. `getRetryAfter()` is available on every
exception that carries an HTTP response: the `Retry-After` header in seconds, or `null`.

## Retries and timeouts

- Requests are retried automatically on **429, 503 and network errors** — up to `maxRetries` times (default 2)
  with exponential backoff. `Retry-After` is honoured; if the server asks to wait longer than `maxRetryDelay`
  (120 s in CLI, 10 s while serving a web request — a page must not hang), the SDK does not wait and throws
  right away: `RateLimitException` for 429, `ServiceUnavailableException` for 503.
- **Planned maintenance is not retried.** A 503 with code `service_disabled` or `maintenance` means the service
  is switched off for maintenance: retrying a second later is pointless, so the exception is thrown at once
  (`$e->isMaintenance()` is `true`). Other 503s (an upstream source temporarily unavailable) are retried.
- **POST requests** (AI chat, OCR, AML screening, batch checks) are retried after a network error only when the
  connection was never established and the request certainly was not sent (DNS, connection refused, connect
  timeout). After a read timeout or a dropped connection they are not: the operation may have run on the server,
  and a retry would charge you twice. See `ConnectionException::isRequestNotSent()`.
- 400, 401, 402, 403 and 404 are never retried.
- Timeouts are in **seconds**, default 30. Heavy operations have their own minimum: `ocr->recognize` and
  `pagespeed->audit` — 120 s, `pagespeed->auditBatch` — 600 s, `imagecheck->analyze` — 60 s,
  `aichat->send` — **660 s (11 minutes)**: a detailed model answer may take up to 10 minutes.

```php
$client = new \Atlorium\Client($key, ['timeout' => 60, 'maxRetries' => 5]);
$client->pagespeed->audit('https://atlorium.com', ['timeout' => 300]); // per call
$fast = $client->withOptions(['maxRetries' => 0]);                      // a copy with other settings
```

A web page should not wait for an external API for minutes: in user-facing request handlers set a short
`timeout` and `maxRetries => 0`, and move heavy checks to background jobs (cron, queues, Bitrix agents).

## Response metadata

A regular call returns the data. If you need the status and headers, call the method via `withRawResponse()`:

```php
$raw = $client->testdata->withRawResponse()->generate(['count' => 5]);
echo $raw->getStatusCode(), ' ', $raw->isSandbox() ? 'sandbox' : 'live', ' ', $raw->getHeader('X-Atlorium-Seed');
$data = $raw->getData();
```

## Custom HTTP client (PSR-18)

```php
$factory = new \GuzzleHttp\Psr7\HttpFactory();
$client = new \Atlorium\Client($key, [
    'transport' => new \Atlorium\Http\Psr18Transport(new \GuzzleHttp\Client(['timeout' => 30]), $factory, $factory),
]);
```

PSR-18 has no timeout setting — configure it in the HTTP client itself. The `psr/http-client` and
`psr/http-factory` packages are required (every compatible client already has them).

## Testing your code

`MockTransport` returns predefined responses and records requests — no network needed:

```php
use Atlorium\Http\MockTransport;
use Atlorium\Http\Response;

$transport = new MockTransport(Response::json(200, ['found' => true, 'inn' => '7707083893']));
$client = new \Atlorium\Client(null, ['transport' => $transport]);

$card = $client->egrul->get('7707083893');
assert($transport->getLastRequest()->getPath() === '/api/egrul/7707083893');
```

A `MockTransport` queue item is a ready `Response`, an exception (it will be thrown) or a
`callable(Request): Response`.

## Development

```bash
composer install
composer test          # PHPUnit
composer analyse       # phpstan, level max, PHP 7.4 syntax
composer cs            # PSR-12 code style
php tests/standalone.php               # works without Composer
python scripts/fetch_specs.py          # refresh the OpenAPI specs
python scripts/generate.py             # regenerate the resources
php scripts/live_smoke.php             # manual check against the live API on the sandbox key
```

Changelog — [CHANGELOG.md](https://github.com/atlorium-api/atlorium-php/blob/main/CHANGELOG.md).

## License

[MIT](https://github.com/atlorium-api/atlorium-php/blob/main/LICENSE)
