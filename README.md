# atlorium/atlorium — официальный PHP SDK

[![Packagist](https://img.shields.io/packagist/v/atlorium/atlorium.svg)](https://packagist.org/packages/atlorium/atlorium)
[![PHP](https://img.shields.io/packagist/php-v/atlorium/atlorium.svg)](https://packagist.org/packages/atlorium/atlorium)
[![CI](https://github.com/atlorium-api/atlorium-php/actions/workflows/ci.yml/badge.svg)](https://github.com/atlorium-api/atlorium-php/actions/workflows/ci.yml)

Проверка контрагентов по ЕГРЮЛ, БИК и SWIFT, адреса ГАР/ФИАС, геокодинг, OCR, DNS, SSL,
погода, ИИ-чат и ещё десяток B2B API для российского рынка — **23 сервиса [Atlorium](https://atlorium.com)
в одном пакете**.

- **Все 59 методов API**, описание параметров и полей ответа прямо в PHPDoc.
- **PHP 7.4+ без зависимостей**: нужны только `ext-curl` и `ext-json`.
- **Работает без Composer** — одна строка `require` в **1С-Битрикс**, WordPress и любом старом проекте.
- **Работает сразу, без регистрации** — на публичном демо-ключе.
- Ретраи на 429/503 с учётом `Retry-After`, понятные исключения на русском, свой PSR-18 клиент по желанию.

English: [README.en.md](https://github.com/atlorium-api/atlorium-php/blob/main/README.en.md)

## Установка

С Composer:

```bash
composer require atlorium/atlorium
```

Без Composer: скачайте архив `atlorium-php-<версия>.zip` со страницы
[Releases](https://github.com/atlorium-api/atlorium-php/releases) и распакуйте его — внутри одна папка
`atlorium-php/`. Подключите автозагрузчик:

```php
require_once '/path/to/atlorium-php/autoload.php';
```

## Быстрый старт

```php
$client = new \Atlorium\Client(); // без ключа — демо-режим

$card = $client->egrul->get('7707083893');
echo $card['shortName'], ' — ', $card['status'];

$bank = $client->cbr->get('044525225');
echo $bank['name'], ' ', $bank['corrAccount'];
```

## Демо-режим

Без ключа клиент работает на **публичном демо-ключе** `ak_sandbox_demo_mockdata_v1`. API отвечает
правдоподобными, но сгенерированными данными (моками): можно встроить и проверить интеграцию до
оплаты. Ответы **детерминированы** — один и тот же запрос всегда даёт один и тот же результат,
поэтому на них удобно писать стабильные тесты. Исключение — поля-отметки времени.

Ограничения частоты запросов в демо-режиме те же, что у зарегистрированного пользователя; условия —
на [atlorium.com/pricing](https://atlorium.com/pricing).

Если ключ не задан ни аргументом, ни переменной `ATLORIUM_API_KEY`, SDK один раз за процесс пишет
предупреждение в `error_log()`: так забытый в проде ключ не превратится в тихую работу на моках. Чтобы
выбрать демо-режим явно и без предупреждения, передайте демо-ключ:
`new \Atlorium\Client(\Atlorium\Client::SANDBOX_API_KEY)`.

## Боевой ключ

Получите ключ на [atlorium.com](https://atlorium.com) и передайте его одним из способов:

```php
$client = new \Atlorium\Client('ak_...'); // явно
// или переменная окружения ATLORIUM_API_KEY — код не меняется
```

`$client->isSandbox()` показывает режим. Ключ не виден в `var_dump`/`print_r`, не попадает в тексты
исключений, а на PHP 8.2+ скрыт и в трассировках стека (`#[\SensitiveParameter]`).

## Сервисы

| Сервис | Ресурс | Методы | Примеры на 6 языках |
|---|---|---|---|
| ЕГРЮЛ/ЕГРИП | `$client->egrul` | `get`, `search`, `getExcerpt` | [egrul-api-client](https://github.com/atlorium-api/egrul-api-client) |
| Справочник БИК ЦБ РФ | `$client->cbr` | `get`, `search`, `getStats` | [cbr-bik-api-client](https://github.com/atlorium-api/cbr-bik-api-client) |
| SWIFT/BIC | `$client->swift` | `get`, `search`, `validate`, `getStats` | [swift-bic-api-client](https://github.com/atlorium-api/swift-bic-api-client) |
| BIN банковской карты | `$client->bin` | `lookup`, `validate` | [bin-lookup-api-client](https://github.com/atlorium-api/bin-lookup-api-client) |
| AML-скоринг криптокошелька | `$client->aml` | `screen`, `validate` | [aml-crypto-screening-api-client](https://github.com/atlorium-api/aml-crypto-screening-api-client) |
| Профиль IP | `$client->ipinfo` | `lookup` | [ip-geolocation-api-client](https://github.com/atlorium-api/ip-geolocation-api-client) |
| Адреса ГАР/ФИАС | `$client->gar` | `search`, `suggest`, `getObject`, `getObjectById`, `getChildren`, `getHierarchy`, `getStats`, `listRegions`, `getRegionStats` | [gar-fias-address-api-client](https://github.com/atlorium-api/gar-fias-address-api-client) |
| Стандартизация адреса | `$client->addressstd` | `standardize` | [address-standardization-api-client](https://github.com/atlorium-api/address-standardization-api-client) |
| Валидация телефона | `$client->phone` | `validate` | [phone-validation-api-client](https://github.com/atlorium-api/phone-validation-api-client) |
| Проверка e-mail | `$client->email` | `validate` | [email-verification-api-client](https://github.com/atlorium-api/email-verification-api-client) |
| Распознавание текста (OCR) | `$client->ocr` | `recognize` | [image-ocr-api-client](https://github.com/atlorium-api/image-ocr-api-client) |
| DNS | `$client->dns` | `lookup`, `checkPropagation` | [dns-lookup-api-client](https://github.com/atlorium-api/dns-lookup-api-client) |
| CIDR-калькулятор | `$client->cidr` | `calculate`, `split`, `check`, `supernet` | [cidr-subnet-calculator-api-client](https://github.com/atlorium-api/cidr-subnet-calculator-api-client) |
| Cron-выражения | `$client->cron` | `evaluate`, `build` | [cron-expression-parser-api-client](https://github.com/atlorium-api/cron-expression-parser-api-client) |
| SSL-сертификат | `$client->certificate` | `check`, `checkUrl`, `checkBatch` | [ssl-certificate-check-api-client](https://github.com/atlorium-api/ssl-certificate-check-api-client) |
| Погода | `$client->weather` | `get` | [weather-api-client](https://github.com/atlorium-api/weather-api-client) |
| ИИ-чат | `$client->aichat` | `send`, `getSession`, `deleteSession`, `listModels` | [ai-chat-api-client](https://github.com/atlorium-api/ai-chat-api-client) |
| Прямой геокодинг | `$client->geocodeforward` | `search`, `searchStructured`, `searchBatch`, `getPlace`, `searchPostcode` | [geocoding-api-client](https://github.com/atlorium-api/geocoding-api-client) |
| Обратный геокодинг | `$client->geocodereverse` | `lookup`, `nearby` | [reverse-geocoding-api-client](https://github.com/atlorium-api/reverse-geocoding-api-client) |
| Генератор тестовых данных РФ | `$client->testdata` | `generate`, `generateWithBody`, `listFields` | [test-data-generator-api-client](https://github.com/atlorium-api/test-data-generator-api-client) |
| Проверка ссылки | `$client->urlcheck` | `check`, `checkBatch` | [url-reputation-api-client](https://github.com/atlorium-api/url-reputation-api-client) |
| Аудит сайта (Core Web Vitals) | `$client->pagespeed` | `audit`, `auditBatch` | [core-web-vitals-api-client](https://github.com/atlorium-api/core-web-vitals-api-client) |
| Модерация изображений | `$client->imagecheck` | `analyze` | [image-moderation-api-client](https://github.com/atlorium-api/image-moderation-api-client) |

Обязательные параметры передаются по порядку, необязательные — последним массивом. Ответы — обычные
ассоциативные массивы; все поля перечислены в PHPDoc метода, IDE покажет их при наборе. Опечатка в имени
параметра даёт `InvalidArgumentException`, а не молча игнорируется.

## Примеры

По одному примеру на каждый сервис:

```php
use Atlorium\Client;
use Atlorium\Enum\CronTemplateType;
use Atlorium\Enum\DayOfWeek;

$client = new Client();

// Контрагенты и банки
$client->egrul->search('Сбербанк', ['limit' => 5]);
file_put_contents('excerpt.pdf', $client->egrul->getExcerpt('7707083893')); // официальная выписка, PDF
$client->cbr->search('Тинькофф');
$client->swift->validate('SABRRUMMXXX');
$client->bin->lookup('424242');
$client->aml->screen('TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE', 'TRX');

// Адреса и карты
$client->gar->suggest('Москва Тверская');
$client->addressstd->standardize('мск тверская 1', ['geocode' => true]);
$client->geocodeforward->search('Казань, Баумана 1', ['size' => 1]);
$client->geocodereverse->lookup(55.7558, 37.6173, ['includeAdmin' => true]);
$client->weather->get(55.7558, 37.6173);

// Контакты
$client->phone->validate('+79161234567');
$client->email->validate('info@example.com');
$client->ipinfo->lookup('8.8.8.8');

// Изображения: путь, байты, поток, \SplFileInfo — SDK сам кодирует в Base64
$client->ocr->recognize('/upload/scan.png');
$client->imagecheck->analyze(file_get_contents('/upload/photo.jpg'), ['includeWebSearch' => true]);

// Сайты и инфраструктура
$client->dns->lookup('atlorium.com');
$client->dns->checkPropagation('atlorium.com', ['resolvers' => ['1.1.1.1', '8.8.8.8']]);
$client->certificate->check('atlorium.com');
$client->urlcheck->checkBatch(['https://example.com', 'https://example.org']);
$client->pagespeed->audit('https://atlorium.com', ['strategy' => 'mobile']);
$client->cidr->calculate('192.168.1.10', 24);
$client->cron->evaluate('*/15 * * * *', ['timeZoneId' => 'Europe/Moscow', 'take' => 3]);
$client->cron->build(['template' => CronTemplateType::WEEKLY, 'timeOfDay' => '09:30:00', 'weekDays' => [DayOfWeek::MONDAY]]);

// ИИ-чат и тестовые данные
$reply = $client->aichat->send('Составь письмо контрагенту о сверке');
$client->aichat->send('Короче', ['sessionId' => $reply['sessionId']]);
$client->aichat->deleteSession($reply['sessionId']); // строка-подтверждение
$client->testdata->generate(['count' => 10, 'fields' => ['fullName', 'innPerson', 'snils'], 'seed' => 42]);
$csv = $client->testdata->generate(['count' => 10, 'format' => 'csv']); // строка CSV
```

## Изображения

`ocr->recognize()` и `imagecheck->analyze()` принимают изображение в любом удобном виде, SDK сам кодирует
его в Base64:

- `\Atlorium\ImageInput` — явно созданный объект (см. ниже);
- `\SplFileInfo` — файл;
- поток (`resource`), например `fopen($path, 'rb')`;
- строка — тогда SDK определяет её смысл по правилам, по порядку:
  1. начинается с `data:` — data-URL, отправляется как есть;
  2. начинается с `http://` или `https://` — `InvalidArgumentException`: API не принимает ссылку,
     скачайте изображение и передайте байты;
  3. путь к существующему файлу — читается файл;
  4. корректный Base64 (после удаления пробелов и переносов строк — не короче 16 символов, длина кратна 4) —
     отправляется как есть;
  5. похожа на путь к картинке (есть `/` или `\`, либо расширение `.png`, `.jpg`, `.pdf` и т.п.), но такого
     файла нет — `InvalidArgumentException` «Файл изображения не найден»;
  6. иначе — байты изображения (например, результат `file_get_contents()`).

Чтобы не полагаться на угадывание, используйте фабрики:

```php
use Atlorium\ImageInput;

$client->ocr->recognize(ImageInput::fromPath('/upload/scan.png'));
$client->ocr->recognize(ImageInput::fromBytes($bytes));
$client->ocr->recognize(ImageInput::fromStream(fopen('/upload/scan.png', 'rb')));
$client->ocr->recognize(ImageInput::fromBase64($base64OrDataUrl));
```

## 1С-Битрикс

SDK не требует Composer и не конфликтует с ядром Битрикса: все классы — в пространстве имён `Atlorium\`.

**1. Подключение.** Распакуйте архив релиза `atlorium-php-<версия>.zip` в `local/php_interface/lib/` —
получится папка `local/php_interface/lib/atlorium-php/`. Добавьте в `local/php_interface/init.php`:

```php
require_once __DIR__ . '/lib/atlorium-php/autoload.php';

/** Один клиент на запрос страницы. */
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

**2. Ключ — не в коде**, а в `bitrix/.settings_extra.php` (файл не попадает в обновления и бэкапы
кода):

```php
<?php
return [
    'atlorium' => ['value' => ['apiKey' => 'ak_...'], 'readonly' => true],
];
```

**3. Кешируйте ответы.** Каждый вызов API тарифицируется, а данные ЕГРЮЛ или БИК не меняются
каждую минуту:

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

**4. Сайт в windows-1251.** API принимает и отдаёт UTF-8. Перекодируйте строки на входе и выходе:

```php
use Bitrix\Main\Text\Encoding;

$query = Encoding::convertEncoding($_GET['q'], SITE_CHARSET, 'UTF-8');
$result = atlorium()->egrul->search($query);
$result = Encoding::convertEncoding($result, 'UTF-8', SITE_CHARSET);
```

Если забыть перекодировать, SDK бросит `InvalidArgumentException` с подсказкой, а не отправит испорченный запрос.

**5. Прокси и старые сертификаты на хостинге** — через опции cURL:

```php
$client = new \Atlorium\Client($key, [
    'transport' => new \Atlorium\Http\CurlTransport([
        CURLOPT_PROXY => 'http://proxy.local:3128',
        // CURLOPT_CAINFO => '/path/to/cacert.pem',
    ]),
]);
```

**6. Долгие операции — в агентах Битрикса или очередях**, а не в обработчике страницы: аудит сайта и
ИИ-чат могут отвечать минутами (см. «Ретраи и таймауты»).

## Ошибки

Все исключения наследуются от `Atlorium\Exception\AtloriumException`:

| HTTP | Исключение | Когда |
|---|---|---|
| 400 | `ValidationException` | неверный формат входных данных |
| 401 | `AuthenticationException` | ключ отсутствует, просрочен или недействителен |
| 402 | `InsufficientCreditsException` | недостаточно кредитов (`getBalance()` — текущий баланс) |
| 403 | `PermissionDeniedException` | сервис не входит в условия вашей учётной записи (код `service_not_in_account_terms`); чтобы подключить его, напишите на support@atlorium.com |
| 404 | `NotFoundException` | объект не найден |
| 429 | `RateLimitException` | превышен лимит запросов (`getRetryAfter()` — через сколько секунд повторить) |
| 503 | `ServiceUnavailableException` | сервис временно недоступен: сбой внешнего источника данных или плановые работы (коды `service_disabled`, `maintenance`; состояние — https://atlorium.com/status). **Деньги за такой ответ не списываются** |
| прочие 5xx | `ServerException` | ошибка на стороне сервиса, деньги не списываются |
| — | `ConnectionException`, `TimeoutException` | сеть, таймаут |

Иерархия:

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
└── ConnectionException             нет ответа (сеть)
    └── TimeoutException            истёк таймаут
```

`catch (ServerException $e)` ловит любую ошибку 5xx, в том числе 503. Прочие коды (405, 415 и т.п.) —
сам `AtloriumException`.

```php
use Atlorium\Exception\AtloriumException;
use Atlorium\Exception\NotFoundException;
use Atlorium\Exception\RateLimitException;
use Atlorium\Exception\ServiceUnavailableException;

try {
    $card = $client->egrul->get($inn);
} catch (NotFoundException $e) {
    // нет в реестре
} catch (RateLimitException $e) {
    echo 'Повторить через ', $e->getRetryAfter(), ' с';
} catch (ServiceUnavailableException $e) {
    echo $e->isMaintenance() ? 'Плановые работы, см. https://atlorium.com/status' : 'Источник данных временно недоступен';
} catch (AtloriumException $e) {
    echo $e->getStatusCode(), ' ', $e->getErrorCode(), ': ', $e->getMessage();
}
```

`getErrorCode()` — стабильный машинный код (`not_found`, `insufficient_credits`, `rate_limited_client`,
`service_not_in_account_terms`, `maintenance` …), по нему и стоит ветвиться. `getRetryAfter()` есть у любого
исключения с HTTP-ответом: значение заголовка `Retry-After` в секундах или `null`.

## Ретраи и таймауты

- Запрос автоматически повторяется на **429, 503 и сетевых ошибках** — до `maxRetries` раз (по умолчанию 2)
  с экспоненциальной задержкой. `Retry-After` учитывается; если сервер просит ждать дольше `maxRetryDelay`
  (120 с в CLI, 10 с при обработке веб-запроса — страница сайта не должна висеть), SDK не ждёт, а сразу
  бросает исключение: `RateLimitException` при 429, `ServiceUnavailableException` при 503.
- **Плановые работы не повторяются.** 503 с кодом `service_disabled` или `maintenance` означает, что сервис
  выключен на время работ: повтор через секунду бесполезен, исключение бросается сразу
  (`$e->isMaintenance()` — `true`). Прочие 503 (внешний источник временно недоступен) повторяются.
- **POST-запросы** (ИИ-чат, OCR, AML-скрининг, пакетные проверки) после сетевой ошибки повторяются, только
  если соединение не установилось и запрос точно не ушёл (DNS, отказ в соединении, таймаут подключения).
  После таймаута чтения или обрыва соединения — нет: операция могла выполниться на сервере, и повтор
  списал бы деньги второй раз. Признак — `ConnectionException::isRequestNotSent()`.
- 400, 401, 402, 403 и 404 не повторяются никогда.
- Таймаут — в **секундах**, по умолчанию 30. Для тяжёлых операций свой минимум: `ocr->recognize` и
  `pagespeed->audit` — 120 с, `pagespeed->auditBatch` — 600 с, `imagecheck->analyze` — 60 с,
  `aichat->send` — **660 с (11 минут)**: развёрнутый ответ модели может готовиться до 10 минут.

```php
$client = new \Atlorium\Client($key, ['timeout' => 60, 'maxRetries' => 5]);
$client->pagespeed->audit('https://atlorium.com', ['timeout' => 300]); // на один вызов
$fast = $client->withOptions(['maxRetries' => 0]);                      // копия с другими настройками
```

Страница сайта не должна ждать внешний API минутами: в обработчиках запросов пользователей ставьте
короткий `timeout` и `maxRetries => 0`, а тяжёлые проверки выносите в фоновые задачи (cron, очереди,
агенты Битрикса).

## Метаданные ответа

Обычный вызов возвращает данные. Нужны статус и заголовки — вызовите метод через `withRawResponse()`:

```php
$raw = $client->testdata->withRawResponse()->generate(['count' => 5]);
echo $raw->getStatusCode(), ' ', $raw->isSandbox() ? 'sandbox' : 'live', ' ', $raw->getHeader('X-Atlorium-Seed');
$data = $raw->getData();
```

## Свой HTTP-клиент (PSR-18)

```php
$factory = new \GuzzleHttp\Psr7\HttpFactory();
$client = new \Atlorium\Client($key, [
    'transport' => new \Atlorium\Http\Psr18Transport(new \GuzzleHttp\Client(['timeout' => 30]), $factory, $factory),
]);
```

Таймаут PSR-18 не передаёт — настройте его в самом HTTP-клиенте. Нужны пакеты `psr/http-client` и
`psr/http-factory` (они есть у всех совместимых клиентов).

## Тесты вашего кода

`MockTransport` отдаёт заранее заданные ответы и запоминает запросы — сеть не нужна:

```php
use Atlorium\Http\MockTransport;
use Atlorium\Http\Response;

$transport = new MockTransport(Response::json(200, ['found' => true, 'inn' => '7707083893']));
$client = new \Atlorium\Client(null, ['transport' => $transport]);

$card = $client->egrul->get('7707083893');
assert($transport->getLastRequest()->getPath() === '/api/egrul/7707083893');
```

Элемент очереди `MockTransport` — готовый `Response`, исключение (будет брошено) или
`callable(Request): Response`.

## Разработка

```bash
composer install
composer test          # PHPUnit
composer analyse       # phpstan, уровень max, синтаксис PHP 7.4
composer cs            # стиль PSR-12
php tests/standalone.php               # работа без Composer
python scripts/fetch_specs.py          # обновить OpenAPI-спеки
python scripts/generate.py             # перегенерировать ресурсы
php scripts/live_smoke.php             # ручная проверка против живого API на демо-ключе
```

История изменений — [CHANGELOG.md](https://github.com/atlorium-api/atlorium-php/blob/main/CHANGELOG.md).

## Лицензия

[MIT](https://github.com/atlorium-api/atlorium-php/blob/main/LICENSE)
