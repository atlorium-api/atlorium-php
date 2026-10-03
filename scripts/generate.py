"""
Генератор ресурсов PHP-SDK из OpenAPI-спек ``spec/<id>_ru.json``.

Что создаётся (перезаписывается при каждом запуске, руками не редактировать):

* ``src/Resource/<Сервис>Resource.php`` — методы, возвращающие данные;
* ``src/Resource/<Сервис>RawResource.php`` — те же методы, возвращающие ``ApiResponse``;
* ``src/Enum/CronTemplateType.php``, ``src/Enum/DayOfWeek.php``;
* ``src/Client.php`` — клиент со свойствами ``$client-><сервис>``.

Таблица ``OPERATIONS`` — единая для всех SDK Atlorium: имена методов, обязательные параметры
(в спеках query-параметры не помечены обязательными, поэтому они заданы здесь) и особые случаи.
Скрипт падает, если в спеке есть операция без записи в таблице или наоборот.

PHP для генерации не нужен. Запуск:
    python scripts/generate.py
"""

from __future__ import annotations

import json
import re
import sys
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parent.parent
SPEC_DIR = ROOT / "spec"
SRC_DIR = ROOT / "src"
RESOURCE_DIR = SRC_DIR / "Resource"
ENUM_DIR = SRC_DIR / "Enum"

NOTE = "СГЕНЕРИРОВАНО scripts/generate.py — не редактировать руками."

JsonObject = dict[str, Any]


@dataclass(frozen=True)
class Service:
    id: str
    prefix: str
    repo: str


SERVICES = [
    Service("egrul", "Egrul", "egrul-api-client"),
    Service("cbr", "Cbr", "cbr-bik-api-client"),
    Service("swift", "Swift", "swift-bic-api-client"),
    Service("bin", "Bin", "bin-lookup-api-client"),
    Service("aml", "Aml", "aml-crypto-screening-api-client"),
    Service("ipinfo", "IpInfo", "ip-geolocation-api-client"),
    Service("gar", "Gar", "gar-fias-address-api-client"),
    Service("addressstd", "AddressStd", "address-standardization-api-client"),
    Service("phone", "Phone", "phone-validation-api-client"),
    Service("email", "Email", "email-verification-api-client"),
    Service("ocr", "Ocr", "image-ocr-api-client"),
    Service("dns", "Dns", "dns-lookup-api-client"),
    Service("cidr", "Cidr", "cidr-subnet-calculator-api-client"),
    Service("cron", "Cron", "cron-expression-parser-api-client"),
    Service("certificate", "Certificate", "ssl-certificate-check-api-client"),
    Service("weather", "Weather", "weather-api-client"),
    Service("aichat", "AiChat", "ai-chat-api-client"),
    Service("geocodeforward", "GeocodeForward", "geocoding-api-client"),
    Service("geocodereverse", "GeocodeReverse", "reverse-geocoding-api-client"),
    Service("testdata", "TestData", "test-data-generator-api-client"),
    Service("urlcheck", "UrlCheck", "url-reputation-api-client"),
    Service("pagespeed", "PageSpeed", "core-web-vitals-api-client"),
    Service("imagecheck", "ImageCheck", "image-moderation-api-client"),
]


@dataclass(frozen=True)
class Op:
    service: str
    http: str
    path: str
    name: str
    required: tuple[str, ...] = ()
    list_params: tuple[str, ...] = ()
    image_params: tuple[str, ...] = ()
    body_name: str | None = None
    default_timeout: float | None = None
    csv: bool = False
    skip_description: bool = False


OPERATIONS = [
    Op("egrul", "GET", "/api/Egrul/{query}", "get"),
    Op("egrul", "GET", "/api/Egrul/search", "search", required=("query",)),
    Op("egrul", "GET", "/api/Egrul/{query}/excerpt", "getExcerpt"),
    Op("cbr", "GET", "/api/Cbr/{bik}", "get"),
    Op("cbr", "GET", "/api/Cbr/search", "search", required=("query",)),
    Op("cbr", "GET", "/api/Cbr/stats", "getStats"),
    Op("swift", "GET", "/api/Swift/{bic}", "get"),
    Op("swift", "GET", "/api/Swift/search", "search", required=("query",)),
    Op("swift", "GET", "/api/Swift/validate/{bic}", "validate"),
    Op("swift", "GET", "/api/Swift/stats", "getStats"),
    Op("bin", "GET", "/api/Bin/{bin}", "lookup"),
    Op("bin", "GET", "/api/Bin/validate/{bin}", "validate"),
    Op("aml", "POST", "/api/Aml/screen", "screen", required=("walletAddress", "blockchain")),
    Op("aml", "GET", "/api/Aml/validate", "validate", required=("walletAddress", "blockchain")),
    Op("ipinfo", "GET", "/api/IpInfo/{ip}", "lookup"),
    Op("gar", "GET", "/api/Gar/search", "search", required=("query",)),
    Op("gar", "GET", "/api/Gar/suggest", "suggest", required=("query",)),
    Op("gar", "GET", "/api/Gar/object/{guid}", "getObject"),
    Op("gar", "GET", "/api/Gar/object/id/{objectId}", "getObjectById"),
    Op("gar", "GET", "/api/Gar/children/{parentObjectId}", "getChildren"),
    Op("gar", "GET", "/api/Gar/hierarchy/{objectId}", "getHierarchy"),
    Op("gar", "GET", "/api/Gar/stats", "getStats"),
    Op("gar", "GET", "/api/Gar/regions", "listRegions"),
    Op("gar", "GET", "/api/Gar/region/{regionCode}/stats", "getRegionStats"),
    Op("addressstd", "GET", "/api/AddressStd", "standardize", required=("address",)),
    Op("phone", "GET", "/api/Phone/{number}", "validate"),
    Op("email", "GET", "/api/EmailValidation", "validate", required=("email",)),
    Op("ocr", "POST", "/api/Ocr/image-to-text", "recognize", required=("image",), image_params=("image",), default_timeout=120),
    Op("dns", "GET", "/api/Dns/lookup", "lookup", required=("domain",)),
    Op("dns", "GET", "/api/Dns/propagation", "checkPropagation", required=("domain",), list_params=("resolvers",)),
    Op("cidr", "GET", "/api/Cidr/calculate", "calculate", required=("ip", "mask")),
    Op("cidr", "GET", "/api/Cidr/subnets", "split", required=("cidr", "newMask")),
    Op("cidr", "GET", "/api/Cidr/check", "check", required=("cidr", "ip")),
    Op("cidr", "POST", "/api/Cidr/supernet", "supernet", required=("inputs",)),
    Op("cron", "POST", "/api/Cron/evaluate", "evaluate", required=("expression",)),
    Op("cron", "POST", "/api/Cron/build", "build", skip_description=True),
    Op("certificate", "GET", "/api/Certificate", "check", required=("host",)),
    Op("certificate", "GET", "/api/Certificate/url", "checkUrl", required=("url",)),
    Op("certificate", "GET", "/api/Certificate/batch", "checkBatch", required=("hosts",), list_params=("hosts",)),
    Op("weather", "GET", "/api/Weather", "get", required=("latitude", "longitude")),
    Op("aichat", "POST", "/api/AiChat/send", "send", required=("message",), default_timeout=660),
    Op("aichat", "GET", "/api/AiChat/session/{sessionId}", "getSession"),
    Op("aichat", "DELETE", "/api/AiChat/session/{sessionId}", "deleteSession"),
    Op("aichat", "GET", "/api/AiChat/models", "listModels"),
    Op("geocodeforward", "GET", "/api/geocodeforward", "search", required=("text",)),
    Op("geocodeforward", "GET", "/api/geocodeforward/structured", "searchStructured"),
    Op("geocodeforward", "POST", "/api/geocodeforward/batch", "searchBatch", required=("queries",)),
    Op("geocodeforward", "GET", "/api/geocodeforward/place", "getPlace", required=("placeId",)),
    Op("geocodeforward", "GET", "/api/geocodeforward/postcode", "searchPostcode", required=("code",)),
    Op("geocodereverse", "GET", "/api/geocodereverse", "lookup", required=("lat", "lon")),
    Op("geocodereverse", "GET", "/api/geocodereverse/nearby", "nearby", required=("lat", "lon")),
    Op("testdata", "GET", "/api/TestData", "generate", list_params=("fields",), csv=True),
    Op("testdata", "POST", "/api/TestData", "generateWithBody", csv=True),
    Op("testdata", "GET", "/api/TestData/fields", "listFields"),
    Op("urlcheck", "GET", "/api/urlcheck", "check", required=("url",)),
    Op("urlcheck", "POST", "/api/urlcheck/batch", "checkBatch", required=("urls",), body_name="urls"),
    Op("pagespeed", "GET", "/api/pagespeed", "audit", required=("url",), default_timeout=120),
    Op("pagespeed", "POST", "/api/pagespeed/batch", "auditBatch", required=("urls",), body_name="urls", default_timeout=600),
    Op("imagecheck", "POST", "/api/imagecheck", "analyze", required=("image",), image_params=("image",), default_timeout=60),
]

# Поля-объекты, которые спека описывает ненулевыми, а сервер может отдать null.
NULLABLE_OVERRIDES: dict[str, set[str]] = {
    "swift": {"headquarters"},
    "ipinfo": {"asn", "company", "flags", "carrier", "privacy", "abuse", "hostedDomains", "abuseReports"},
    "addressstd": {"gar", "geo"},
    "weather": {"rain", "snow"},
    "pagespeed": {"fieldData"},
    "imagecheck": {"webSearch"},
}

# В спеке это integer без enum; значения — по поведению сервера.
INT_ENUMS: dict[str, list[tuple[str, int, str]]] = {
    "CronTemplateType": [
        ("EVERY_MINUTE", 0, "Каждую минуту."),
        ("EVERY_N_MINUTES", 1, "Каждые N минут (шаг — interval)."),
        ("HOURLY", 2, "Раз в час, в начале часа."),
        ("EVERY_N_HOURS", 3, "Каждые N часов (шаг — interval)."),
        ("DAILY", 4, "Раз в сутки, в timeOfDay."),
        ("WEEKLY", 5, "В выбранные дни недели (weekDays), в timeOfDay."),
        ("MONTHLY", 6, "Раз в месяц: число dayOfMonth, время timeOfDay."),
    ],
    "DayOfWeek": [
        ("SUNDAY", 0, "Воскресенье."),
        ("MONDAY", 1, "Понедельник."),
        ("TUESDAY", 2, "Вторник."),
        ("WEDNESDAY", 3, "Среда."),
        ("THURSDAY", 4, "Четверг."),
        ("FRIDAY", 5, "Пятница."),
        ("SATURDAY", 6, "Суббота."),
    ],
}

CALL_OPTIONS_SHAPE = "timeout?: float|int, maxRetries?: int, headers?: array<string, string>"


def fail(message: str) -> None:
    raise SystemExit(message)


def ref_name(ref: str) -> str:
    return ref.rsplit("/", 1)[1]


def php_string(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def doc_text(text: str) -> list[str]:
    """Текст для PHPDoc: без «*/», без хвостовых пробелов, строки с @ в начале экранированы."""
    lines = []
    for line in str(text or "").strip().replace("*/", "*\\/").splitlines():
        line = line.rstrip()
        lines.append("\\" + line if line.lstrip().startswith("@") else line)
    return lines


def first_sentence(text: str, limit: int = 140) -> str:
    flat = " ".join(str(text or "").split())
    if not flat:
        return ""
    match = re.match(r"(.+?[.!?])(\s|$)", flat)
    sentence = match.group(1) if match else flat
    return sentence if len(sentence) <= limit else sentence[: limit - 1].rstrip() + "…"


def phpdoc_block(lines: list[str], indent: str) -> str:
    out = [f"{indent}/**"]
    for line in lines:
        out.append(f"{indent} * {line}".rstrip() if line else f"{indent} *")
    out.append(f"{indent} */")
    return "\n".join(out) + "\n"


# ── Типы для PHPDoc ────────────────────────────────────────────────────────────────


def doc_type(schema: JsonObject, schemas: dict[str, JsonObject]) -> tuple[str, bool]:
    """JSON-схема → (тип PHPDoc, допускает null)."""
    ref = schema.get("$ref")
    if isinstance(ref, str):
        name = ref_name(ref)
        if name in INT_ENUMS:
            return "int", False
        return doc_type(schemas[name], schemas)
    all_of = schema.get("allOf")
    if isinstance(all_of, list) and len(all_of) == 1:
        return doc_type(all_of[0], schemas)
    for key in ("oneOf", "anyOf"):
        variants = schema.get(key)
        if isinstance(variants, list):
            nullable = False
            parts: list[str] = []
            for variant in variants:
                if variant.get("type") == "null":
                    nullable = True
                    continue
                expr, variant_nullable = doc_type(variant, schemas)
                nullable = nullable or variant_nullable
                if expr not in parts:
                    parts.append(expr)
            return "|".join(parts) or "mixed", nullable
    enum = schema.get("enum")
    if isinstance(enum, list):
        return "|".join(php_string(v) if isinstance(v, str) else str(v) for v in enum), False
    raw = schema.get("type")
    types = raw if isinstance(raw, list) else ([raw] if raw else [])
    nullable = "null" in types
    types = [t for t in types if t != "null"]
    if not types and ("properties" in schema or "additionalProperties" in schema):
        types = ["object"]
    if not types:
        return "mixed", nullable
    if "integer" in types:
        return "int", nullable
    if "number" in types:
        return "float", nullable
    if types == ["string"]:
        return "string", nullable
    if types == ["boolean"]:
        return "bool", nullable
    if types == ["array"]:
        item, _ = doc_type(schema.get("items", {}), schemas)
        return f"list<{item}>", nullable
    return "array<string, mixed>", nullable


def with_null(expr: str, nullable: bool) -> str:
    return f"{expr}|null" if nullable and expr != "mixed" else expr


@dataclass
class Param:
    api: str
    location: str  # path | query | body | bodyArray
    required: bool
    doc: str
    native: str | None
    kind: str  # plain | list | bodyList | image
    description: str


def param_info(op: Op, name: str, schema: JsonObject, location: str, schemas: dict[str, JsonObject]) -> tuple[str, str | None, str]:
    """(тип PHPDoc, нативный тип для позиционного аргумента или None, вид)."""
    if name in op.image_params:
        return "ImageInput|\\SplFileInfo|resource|string", None, "image"
    if name in op.list_params:
        return "string|list<string>", None, "list"
    if op.csv and name == "format":
        return "'json'|'csv'", None, "plain"
    if schema.get("format") == "date-time":
        return "\\DateTimeInterface|string", None, "plain"
    raw = schema.get("type")
    types = raw if isinstance(raw, list) else [raw]
    if "array" in types:
        item, _ = doc_type(schema.get("items", {}), schemas)
        if item == "string":
            return "string|list<string>", None, "bodyList"
        return f"list<{item}>", "array", "plain"
    if location == "path" and "integer" in types:
        return "int|string", None, "plain"
    expr, _ = doc_type(schema, schemas)
    native = expr if expr in ("string", "int", "float", "bool") else None
    return expr, native, "plain"


def collect_params(op: Op, spec_op: JsonObject, schemas: dict[str, JsonObject]) -> list[Param]:
    params: list[Param] = []

    def add(name: str, location: str, required: bool, schema: JsonObject, description: Any) -> None:
        doc, native, kind = param_info(op, name, schema, location, schemas)
        params.append(Param(name, location, required, doc, native, kind, str(description or "").strip()))

    for raw in spec_op.get("parameters", []):
        if raw["in"] not in ("path", "query"):
            fail(f"{op.service}.{op.name}: параметр в {raw['in']}")
        add(raw["name"], raw["in"], raw["in"] == "path" or raw["name"] in op.required, raw.get("schema", {}), raw.get("description"))

    body = spec_op.get("requestBody", {}).get("content", {}).get("application/json", {}).get("schema")
    if isinstance(body, dict):
        if "$ref" in body:
            for name, prop in schemas[ref_name(body["$ref"])].get("properties", {}).items():
                add(name, "body", name in op.required, prop, prop.get("description"))
        elif body.get("type") == "array":
            if not op.body_name:
                fail(f"{op.service}.{op.name}: тело-массив без body_name")
            description = spec_op["requestBody"].get("description") or "Список значений."
            params.append(Param(op.body_name, "bodyArray", True, "string|list<string>", None, "bodyList", description))
        else:
            fail(f"{op.service}.{op.name}: неизвестная форма тела")

    names = [p.api for p in params]
    missing = set(op.required) - set(names)
    if missing:
        fail(f"{op.service}.{op.name}: обязательные параметры не найдены в спеке: {missing}")
    if len(names) != len(set(names)) or set(names) & {"timeout", "maxRetries", "headers", "params"}:
        fail(f"{op.service}.{op.name}: конфликт имён параметров {names}")
    return params


def response_info(op: Op, spec_op: JsonObject, schemas: dict[str, JsonObject]) -> tuple[str, str | None, str, JsonObject | None]:
    """(тип @return, нативный тип возврата, responseType, схема объекта для описания полей)."""
    content: JsonObject = spec_op.get("responses", {}).get("200", {}).get("content", {})
    if "application/pdf" in content:
        return "string", "string", "bytes", None
    schema = (content.get("application/json") or {}).get("schema") or {}
    if schema.get("type") == "string":
        return "string", "string", "auto", None
    if schema.get("type") == "array":
        item = schema.get("items", {})
        described = schemas.get(ref_name(item["$ref"])) if "$ref" in item else None
        return "list<array<string, mixed>>", "array", "json", described
    described = schemas.get(ref_name(schema["$ref"])) if "$ref" in schema else None
    if op.csv:
        return "array<string, mixed>|string", None, "auto", described
    return "array<string, mixed>", "array", "json", described


def field_lines(service_id: str, schema: JsonObject | None, schemas: dict[str, JsonObject]) -> list[str]:
    if not schema or not isinstance(schema.get("properties"), dict):
        return []
    overrides = NULLABLE_OVERRIDES.get(service_id, set())
    lines = []
    for name, prop in schema["properties"].items():
        expr, nullable = doc_type(prop, schemas)
        expr = with_null(expr, nullable or name in overrides)
        description = first_sentence(prop.get("description", ""))
        lines.append(f"    - {name} ({expr})" + (f" — {description}" if description else ""))
    return lines


# ── Генерация ───────────────────────────────────────────────────────────────────────


def render_path(op: Op) -> str:
    parts = re.split(r"(\{[^}]+\})", op.path)
    pieces: list[str] = []
    literal = ""
    for part in parts:
        if part.startswith("{"):
            if literal:
                pieces.append(php_string(literal))
                literal = ""
            pieces.append(f"self::segment(${part[1:-1]})")
        else:
            literal += part.lower()
    if literal:
        pieces.append(php_string(literal))
    return " . ".join(pieces)


def value_expr(param: Param) -> str:
    if param.kind == "image":
        return f"ImageInput::encode(${param.api})"
    if param.kind == "bodyList":
        return f"self::toList(${param.api})"
    return f"${param.api}"


def php_array(entries: list[tuple[str, str]], indent: str) -> str:
    if not entries:
        return "[]"
    inner = "".join(f"{indent}    {php_string(key)} => {value},\n" for key, value in entries)
    return f"[\n{inner}{indent}]"


def php_list(values: list[str]) -> str:
    return "[" + ", ".join(php_string(v) for v in values) + "]"


def generate_service(service: Service, spec: JsonObject) -> tuple[str, str, int]:
    schemas: dict[str, JsonObject] = spec.get("components", {}).get("schemas", {})
    ops = [op for op in OPERATIONS if op.service == service.id]
    spec_ops = {
        (method.upper(), path): body
        for path, methods in spec["paths"].items()
        for method, body in methods.items()
        if method in ("get", "post", "put", "delete", "patch")
    }
    table = {(op.http, op.path) for op in ops}
    if set(spec_ops) != table:
        fail(f"{service.id}: таблица не совпадает со спекой: {set(spec_ops) ^ table}")

    title = spec.get("info", {}).get("title", service.id)
    cls = f"{service.prefix}Resource"
    raw_cls = f"{service.prefix}RawResource"
    methods: list[str] = []
    raw_methods: list[str] = []
    uses_image = False

    for op in ops:
        spec_op = spec_ops[(op.http, op.path)]
        params = collect_params(op, spec_op, schemas)
        order = {name: i for i, name in enumerate(op.required)}
        positional = [p for p in params if p.location == "path"] + sorted(
            (p for p in params if p.location != "path" and p.required), key=lambda p: order.get(p.api, 99)
        )
        optional = [p for p in params if not p.required]
        returns, native_return, response_type, described = response_info(op, spec_op, schemas)
        uses_image = uses_image or any(p.kind == "image" for p in params)

        signature = ", ".join(
            [f"{p.native + ' ' if p.native else ''}${p.api}" for p in positional] + ["array $params = []"]
        )
        call_args = ", ".join([f"${p.api}" for p in positional] + ["$params"])
        shape = ", ".join([f"{p.api}?: {p.doc}" for p in optional] + [CALL_OPTIONS_SHAPE])

        # Описание операции.
        summary = str(spec_op.get("summary") or "").strip() or op.name
        description = "" if op.skip_description else str(spec_op.get("description") or "").strip()
        doc: list[str] = doc_text(summary)
        if description and description != summary:
            doc += [""] + doc_text(description)
        doc.append("")
        for p in positional:
            doc.append(f"@param {p.doc} ${p.api} " + (" ".join(p.description.split()) or "—"))
        doc.append(f"@param array{{{shape}}} $params")
        if optional:
            doc.append("    Необязательные параметры:")
            for p in optional:
                text = first_sentence(p.description, 200)
                doc.append(f"    - {p.api}" + (f" — {text}" if text else ""))
        doc.append("    Настройки вызова: timeout (секунды), maxRetries, headers.")
        doc.append("")
        return_doc = f"@return {returns}"
        if response_type == "bytes":
            return_doc += " содержимое файла"
        elif op.csv:
            return_doc += " при format = 'csv' — строка CSV, иначе массив"
        doc.append(return_doc)
        fields = field_lines(service.id, described, schemas)
        if fields:
            doc.append("    Поля ответа" + (" (каждого элемента)" if returns.startswith("list<") else "") + ":")
            doc += fields
        doc += ["", "@throws AtloriumException"]

        native = f": {native_return}" if native_return else ""
        if native_return == "string" and response_type == "auto":
            # По спеке строка, но сервер вправе ответить JSON-объектом или пустым телом — приводим к строке.
            body = f"        return self::text($this->raw->{op.name}({call_args})->getData());\n"
        else:
            var_type = "string" if native_return == "string" else returns
            body = (
                f"        /** @var {var_type} $data */\n"
                + f"        $data = $this->raw->{op.name}({call_args})->getData();\n\n"
                + "        return $data;\n"
            )
        methods.append(
            phpdoc_block(doc, "    ")
            + f"    public function {op.name}({signature}){native}\n    {{\n{body}    }}\n"
        )

        # Сырой вариант: собирает описание запроса.
        query_required = [(p.api, value_expr(p)) for p in params if p.location == "query" and p.required]
        query_optional = [p.api for p in optional if p.location == "query"]
        body_required = [(p.api, value_expr(p)) for p in params if p.location == "body" and p.required]
        body_optional = [p.api for p in optional if p.location == "body"]
        body_lists = [p.api for p in optional if p.location == "body" and p.kind == "bodyList"]
        body_array = next((p for p in params if p.location == "bodyArray"), None)
        has_body_schema = any(p.location == "body" for p in params)

        spec_entries: list[tuple[str, str]] = [("method", php_string(op.http)), ("path", render_path(op))]
        if query_required:
            spec_entries.append(("query", php_array(query_required, "            ")))
        if query_optional:
            spec_entries.append(("optionalQuery", php_list(query_optional)))
        if has_body_schema:
            spec_entries.append(("body", php_array(body_required, "            ")))
            if body_optional:
                spec_entries.append(("optionalBody", php_list(body_optional)))
            if body_lists:
                spec_entries.append(("bodyLists", php_list(body_lists)))
        elif body_array is not None:
            spec_entries.append(("json", value_expr(body_array)))
        elif op.http == "POST":
            spec_entries.append(("body", "[]"))
        if op.default_timeout is not None:
            spec_entries.append(("defaultTimeout", f"{op.default_timeout:.1f}"))
        if response_type != "json":
            spec_entries.append(("responseType", php_string(response_type)))

        raw_doc = [f"Как {{@see {cls}::{op.name}()}}, но возвращает данные вместе с HTTP-метаданными.", ""]
        raw_doc += [f"@param {p.doc} ${p.api}" for p in positional]
        raw_doc += [f"@param array{{{shape}}} $params", "", "@throws AtloriumException"]
        raw_methods.append(
            phpdoc_block(raw_doc, "    ")
            + f"    public function {op.name}({signature}): ApiResponse\n    {{\n"
            + f"        return $this->client->send({php_array(spec_entries, '        ')}, $params);\n    }}\n"
        )

    link = f"https://github.com/atlorium-api/{service.repo}"
    uses = ["Atlorium\\ApiResponse", "Atlorium\\BaseClient", "Atlorium\\Exception\\AtloriumException"]
    raw_uses = ["Atlorium\\ApiResponse", "Atlorium\\Exception\\AtloriumException"]
    if uses_image:
        uses.append("Atlorium\\ImageInput")
        raw_uses.append("Atlorium\\ImageInput")
    uses.remove("Atlorium\\ApiResponse")

    resource = (
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace Atlorium\\Resource;\n\n"
        + "".join(f"use {u};\n" for u in sorted(uses))
        + "\n"
        + phpdoc_block([f"{title}: $client->{service.id}.", "", f"Примеры на шести языках: {link}", "", NOTE], "")
        + f"class {cls} extends AbstractResource\n{{\n"
        + f"    private {raw_cls} $raw;\n\n"
        + "    public function __construct(BaseClient $client)\n    {\n        parent::__construct($client);\n"
        + f"        $this->raw = new {raw_cls}($client);\n    }}\n\n"
        + phpdoc_block(["Те же методы, но каждый возвращает {@see ApiResponse}: данные вместе со статусом и заголовками."], "    ")
        + f"    public function withRawResponse(): {raw_cls}\n    {{\n        return $this->raw;\n    }}\n\n"
        + "\n".join(methods)
        + "}\n"
    )
    resource = resource.replace("use Atlorium\\BaseClient;\n", "use Atlorium\\ApiResponse;\nuse Atlorium\\BaseClient;\n", 1)
    raw_resource = (
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace Atlorium\\Resource;\n\n"
        + "".join(f"use {u};\n" for u in sorted(raw_uses))
        + "\n"
        + phpdoc_block([f"{title}: методы, возвращающие {{@see ApiResponse}}. Получить: $client->{service.id}->withRawResponse().", "", NOTE], "")
        + f"class {raw_cls} extends AbstractResource\n{{\n"
        + "\n".join(raw_methods)
        + "}\n"
    )
    return resource, raw_resource, len(ops)


def generate_enum(name: str, description: str) -> str:
    out = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Atlorium\\Enum;\n\n"
    out += phpdoc_block(doc_text(description or name) + ["", NOTE], "")
    out += f"final class {name}\n{{\n"
    for member, value, comment in INT_ENUMS[name]:
        out += f"    /** {comment} */\n    public const {member} = {value};\n\n"
    out += "    private function __construct()\n    {\n    }\n}\n"
    return out


def generate_client(titles: dict[str, str]) -> str:
    out = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Atlorium;\n\n"
    # php-cs-fixer (ordered_imports) требует алфавитный порядок.
    imports = ["Atlorium\\Resource\\AbstractResource"] + [f"Atlorium\\Resource\\{s.prefix}Resource" for s in SERVICES]
    out += "".join(f"use {name};\n" for name in sorted(imports, key=str.lower))
    doc = [
        "Клиент API Atlorium.",
        "",
        "    $client = new \\Atlorium\\Client();            // без ключа — демо-режим, ответы-моки",
        "    $card = $client->egrul->get('7707083893');",
        "",
    ]
    doc += [f"@property-read {s.prefix}Resource ${s.id} {titles[s.id]}" for s in SERVICES]
    doc += ["", NOTE]
    out += "\n" + phpdoc_block(doc, "")
    out += "class Client extends BaseClient\n{\n"
    out += "    /** @var array<string, class-string<AbstractResource>> */\n    private const RESOURCES = [\n"
    for s in SERVICES:
        out += f"        '{s.id}' => {s.prefix}Resource::class,\n"
    out += "    ];\n\n    /** @var array<string, AbstractResource> */\n    private array $resources = [];\n\n"
    out += """    /**
     * Сервис по имени: $client->egrul, $client->cbr и т.д. Объект ресурса создаётся при первом обращении.
     *
     * @return AbstractResource
     */
    public function __get(string $name)
    {
        if (!isset(self::RESOURCES[$name])) {
            throw new \\OutOfRangeException(sprintf(
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
        throw new \\LogicException('Свойства клиента Atlorium доступны только для чтения.');
    }

    public function __clone()
    {
        // Копия из withOptions() должна создавать собственные ресурсы, привязанные к ней самой.
        $this->resources = [];
    }
}
"""
    return out


def main() -> int:
    if {op.service for op in OPERATIONS} != {s.id for s in SERVICES}:
        fail("Сервисы таблицы и списка расходятся")

    RESOURCE_DIR.mkdir(parents=True, exist_ok=True)
    ENUM_DIR.mkdir(parents=True, exist_ok=True)
    for path in list(RESOURCE_DIR.glob("*.php")) + list(ENUM_DIR.glob("*.php")):
        if path.name != "AbstractResource.php":
            path.unlink()

    titles: dict[str, str] = {}
    total = 0
    for service in SERVICES:
        spec = json.loads((SPEC_DIR / f"{service.id}_ru.json").read_text(encoding="utf-8"))
        titles[service.id] = str(spec.get("info", {}).get("title", service.id))
        resource, raw_resource, count = generate_service(service, spec)
        (RESOURCE_DIR / f"{service.prefix}Resource.php").write_text(resource, encoding="utf-8", newline="\n")
        (RESOURCE_DIR / f"{service.prefix}RawResource.php").write_text(raw_resource, encoding="utf-8", newline="\n")
        total += count
        if service.id == "cron":
            schemas = spec["components"]["schemas"]
            for name in INT_ENUMS:
                (ENUM_DIR / f"{name}.php").write_text(
                    generate_enum(name, str(schemas[name].get("description") or name)), encoding="utf-8", newline="\n"
                )

    (SRC_DIR / "Client.php").write_text(generate_client(titles), encoding="utf-8", newline="\n")
    print(f"Сгенерировано: {len(SERVICES)} сервисов, {total} операций.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
