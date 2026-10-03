<?php

declare(strict_types=1);

namespace Atlorium;

use Atlorium\Resource\AbstractResource;
use Atlorium\Resource\AddressStdResource;
use Atlorium\Resource\AiChatResource;
use Atlorium\Resource\AmlResource;
use Atlorium\Resource\BinResource;
use Atlorium\Resource\CbrResource;
use Atlorium\Resource\CertificateResource;
use Atlorium\Resource\CidrResource;
use Atlorium\Resource\CronResource;
use Atlorium\Resource\DnsResource;
use Atlorium\Resource\EgrulResource;
use Atlorium\Resource\EmailResource;
use Atlorium\Resource\GarResource;
use Atlorium\Resource\GeocodeForwardResource;
use Atlorium\Resource\GeocodeReverseResource;
use Atlorium\Resource\ImageCheckResource;
use Atlorium\Resource\IpInfoResource;
use Atlorium\Resource\OcrResource;
use Atlorium\Resource\PageSpeedResource;
use Atlorium\Resource\PhoneResource;
use Atlorium\Resource\SwiftResource;
use Atlorium\Resource\TestDataResource;
use Atlorium\Resource\UrlCheckResource;
use Atlorium\Resource\WeatherResource;

/**
 * Клиент API Atlorium.
 *
 *     $client = new \Atlorium\Client();            // без ключа — демо-режим, ответы-моки
 *     $card = $client->egrul->get('7707083893');
 *
 * @property-read EgrulResource $egrul API ЕГРЮЛ/ЕГРИП
 * @property-read CbrResource $cbr API справочника БИК
 * @property-read SwiftResource $swift API справочника SWIFT/BIC
 * @property-read BinResource $bin API BIN Lookup
 * @property-read AmlResource $aml API AML-скоринга
 * @property-read IpInfoResource $ipinfo API профиля IP
 * @property-read GarResource $gar ГАР/ФИАС API
 * @property-read AddressStdResource $addressstd API стандартизации адреса
 * @property-read PhoneResource $phone API валидации телефона
 * @property-read EmailResource $email API проверки почты
 * @property-read OcrResource $ocr API распознавания текста с изображения
 * @property-read DnsResource $dns API проверки DNS
 * @property-read CidrResource $cidr API калькулятора подсетей
 * @property-read CronResource $cron API разбора Cron-выражений
 * @property-read CertificateResource $certificate API проверки SSL-сертификата
 * @property-read WeatherResource $weather API погодных данных
 * @property-read AiChatResource $aichat API ИИ-чата
 * @property-read GeocodeForwardResource $geocodeforward API прямого геокодинга
 * @property-read GeocodeReverseResource $geocodereverse API обратного геокодинга
 * @property-read TestDataResource $testdata API генератора тестовых данных РФ
 * @property-read UrlCheckResource $urlcheck API проверки ссылки
 * @property-read PageSpeedResource $pagespeed API аудита сайта
 * @property-read ImageCheckResource $imagecheck API анализа изображения
 *
 * СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками.
 */
class Client extends BaseClient
{
    /** @var array<string, class-string<AbstractResource>> */
    private const RESOURCES = [
        'egrul' => EgrulResource::class,
        'cbr' => CbrResource::class,
        'swift' => SwiftResource::class,
        'bin' => BinResource::class,
        'aml' => AmlResource::class,
        'ipinfo' => IpInfoResource::class,
        'gar' => GarResource::class,
        'addressstd' => AddressStdResource::class,
        'phone' => PhoneResource::class,
        'email' => EmailResource::class,
        'ocr' => OcrResource::class,
        'dns' => DnsResource::class,
        'cidr' => CidrResource::class,
        'cron' => CronResource::class,
        'certificate' => CertificateResource::class,
        'weather' => WeatherResource::class,
        'aichat' => AiChatResource::class,
        'geocodeforward' => GeocodeForwardResource::class,
        'geocodereverse' => GeocodeReverseResource::class,
        'testdata' => TestDataResource::class,
        'urlcheck' => UrlCheckResource::class,
        'pagespeed' => PageSpeedResource::class,
        'imagecheck' => ImageCheckResource::class,
    ];

    /** @var array<string, AbstractResource> */
    private array $resources = [];

    /**
     * Сервис по имени: $client->egrul, $client->cbr и т.д. Объект ресурса создаётся при первом обращении.
     *
     * @return AbstractResource
     */
    public function __get(string $name)
    {
        if (!isset(self::RESOURCES[$name])) {
            throw new \OutOfRangeException(sprintf(
                'У клиента Atlorium нет сервиса «%s». Доступные: %s.',
                $name,
                implode(', ', array_keys(self::RESOURCES))
            ));
        }
        if (!isset($this->resources[$name])) {
            $class = self::RESOURCES[$name];
            $this->resources[$name] = new $class($this);
        }

        return $this->resources[$name];
    }

    public function __isset(string $name): bool
    {
        return isset(self::RESOURCES[$name]);
    }

    /** @param mixed $value */
    public function __set(string $name, $value): void
    {
        throw new \LogicException('Свойства клиента Atlorium доступны только для чтения.');
    }

    public function __clone()
    {
        // Копия из withOptions() должна создавать собственные ресурсы, привязанные к ней самой.
        $this->resources = [];
    }
}
