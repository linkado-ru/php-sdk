# Linkado PHP SDK

Официальный PHP SDK для отправки событий в Linkado и создания одноразовых SSO-ссылок. Пакет поддерживает PHP 8.2+ и использует Saloon v4.

SDK покрывает только подтверждённые v1-сценарии:

- `POST /api/v1/events` — события клиентов, лидов, оплат и подписок;
- `POST /api/v1/sso-links` — ссылка в партнёрский кабинет;
- tracking clicks намеренно не входит в PHP SDK.

## Установка

```bash
composer require linkado-ru/php-sdk
```

Linkado выдаёт Bearer credential и URL API отдельно. Production URL нельзя выводить из имени продукта, поэтому `baseUrl` обязателен. Храните оба значения в environment-backed конфигурации приложения:

```dotenv
LINKADO_TOKEN=integration-credential
LINKADO_BASE_URL=https://linkado.example/api/v1/
```

```php
use Linkado\PhpSdk\LinkadoConnector;

$linkado = new LinkadoConnector(
    token: (string) getenv('LINKADO_TOKEN'),
    baseUrl: (string) getenv('LINKADO_BASE_URL'),
);
```

SDK нормализует URL до одного завершающего `/`, добавляет Bearer authentication и JSON-заголовки. Credential помечен как sensitive, не попадает в debug-представление, а сериализация connector запрещена.

## События

Событие отправляется методом `$linkado->events()->send($event)`. Ответ содержит внутренний `id`, ваш исходный `event_id`, типизированный `status`, необязательный `result` и список `warnings`.

Linkado отвечает `202` при первом приёме, `200` при идентичном повторе и `409`, если тот же `event_id` пришёл с другим payload. Лимит endpoint — 300 запросов в минуту.

### Клиент, лид и атрибуция

Для клиента и лида разрешён ровно один источник атрибуции: `click_id` или `referral_slug`. Можно не передавать ни одного, но нельзя передавать оба.

```php
use Linkado\PhpSdk\DataObjects\CustomerCreatedEventData;
use Linkado\PhpSdk\DataObjects\LeadCreatedEventData;

$customer = new CustomerCreatedEventData(
    event_id: '01K5G6XYBN7QPF9G0AJM1T2E3R',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-123',
    click_id: 'click-456',
);

$lead = new LeadCreatedEventData(
    event_id: '01K5G72DZZ6S3K1W8A4V9N0M2Q',
    program_key: 'program-public-key',
    occurred_at: '2026-09-19T12:00:00Z',
    external_customer_id: 'customer-123',
    referral_slug: 'partner-slug',
);

$unattributed = new CustomerCreatedEventData(
    event_id: '01K5G74CBJG9TBRXM38WQ4D5NV',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-456',
);

$response = $linkado->events()->send($customer);
```

Объекты дат сериализуются в UTC RFC 3339. Строковые даты отправляются без изменения.

### Успешная оплата

Суммы всегда передаются целыми положительными числами в minor units: например, `129900` для `1299.00 RUB`.

```php
use Linkado\PhpSdk\DataObjects\EventMetadataData;
use Linkado\PhpSdk\DataObjects\PaymentSucceededEventData;
use Linkado\PhpSdk\Enums\PaymentKind;

$initialPayment = new PaymentSucceededEventData(
    event_id: '01K5G78M4QFMC7Y6XVB2DBZK13',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-123',
    external_payment_id: 'payment-1001',
    amount_minor: 129900,
    currency: 'RUB',
    payment_kind: PaymentKind::SubscriptionInitial,
    external_subscription_id: 'subscription-100',
    metadata: new EventMetadataData(
        source: 'billing',
        plan_code: 'pro',
        billing_reason: 'subscription_create',
    ),
);

$linkado->events()->send($initialPayment);
```

Поддерживаются четыре значения `PaymentKind`:

- `SubscriptionInitial` и `SubscriptionChange` требуют `external_subscription_id`;
- `TopUp` и `OneTime` запрещают `external_subscription_id`.

```php
$subscriptionChange = new PaymentSucceededEventData(
    event_id: '01K5G79Z6CXV4D1QABMR8E2N7P',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-123',
    external_payment_id: 'payment-1002',
    amount_minor: 159900,
    currency: 'RUB',
    payment_kind: PaymentKind::SubscriptionChange,
    external_subscription_id: 'subscription-100',
);

$topUp = new PaymentSucceededEventData(
    event_id: '01K5G7A3TW9F2SN6HVCX01QM8R',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-123',
    external_payment_id: 'payment-1003',
    amount_minor: 50000,
    currency: 'RUB',
    payment_kind: PaymentKind::TopUp,
);

$oneTime = new PaymentSucceededEventData(
    event_id: '01K5G7AF0N8X2P4VSBQCK6H3MT',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-123',
    external_payment_id: 'payment-1004',
    amount_minor: 9900,
    currency: 'RUB',
    payment_kind: PaymentKind::OneTime,
);
```

### Продление подписки

```php
use Linkado\PhpSdk\DataObjects\SubscriptionRenewedEventData;

$linkado->events()->send(new SubscriptionRenewedEventData(
    event_id: '01K5G7BZ4P6EJYQA59RKF2H8MW',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-123',
    external_payment_id: 'payment-1002',
    external_subscription_id: 'subscription-100',
    amount_minor: 129900,
    currency: 'RUB',
));
```

### Отмена подписки

```php
use Linkado\PhpSdk\DataObjects\SubscriptionCancelledEventData;

$linkado->events()->send(new SubscriptionCancelledEventData(
    event_id: '01K5G7FVM5XJ3P9N0C6DHT4A2S',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-123',
    external_subscription_id: 'subscription-100',
));
```

### Возврат

`refunded_amount_minor` — сумма именно текущего возврата, а не накопленный итог по оплате. Для нескольких частичных возвратов отправляйте отдельное событие с отдельным `external_refund_id`.

```php
use Linkado\PhpSdk\DataObjects\PaymentRefundedEventData;

$linkado->events()->send(new PaymentRefundedEventData(
    event_id: '01K5G7JMX1W4NP3VA9YH80Z6KC',
    program_key: 'program-public-key',
    occurred_at: new DateTimeImmutable('now'),
    external_customer_id: 'customer-123',
    external_payment_id: 'payment-1002',
    external_refund_id: 'refund-2001',
    refunded_amount_minor: 30000,
    currency: 'RUB',
));
```

## Metadata и персональные данные

Допустимы только `source`, `source_event`, `plan_code`, `billing_reason` и `checkout_session_id`. Их значения должны быть scalar, а весь объект — не больше 4096 байт в canonical JSON. `null` исключается, но `false`, `0` и пустая строка сохраняются.

Не отправляйте email, телефон, IP, налоговые и платёжные идентификаторы, Bearer credentials или другие PII/secrets. SDK блокирует распространённые PII-паттерны, но ответственность за безопасное содержание данных остаётся у приложения.

## SSO-ссылка

SSO endpoint ограничен 60 запросами в минуту. Поддерживается только переход `affiliate_portal`. URL ответа содержит одноразовый секрет: не записывайте его в логи, аналитику или error context.

```php
use Linkado\PhpSdk\DataObjects\CreateSsoLinkData;
use Linkado\PhpSdk\Enums\SsoRedirect;

$link = $linkado->ssoLinks()->create(new CreateSsoLinkData(
    program_key: 'program-public-key',
    external_user_id: 'merchant-user-123',
    email: 'partner@example.com',
    email_verified: true,
    display_name: 'Partner Name',
    redirect_to: SsoRedirect::AffiliatePortal,
));

// Немедленно перенаправьте пользователя, не логируя $link->url.
return redirect()->away($link->url);
```

Если email неизвестен, передайте `null` и `email_verified: false`: поле `email` не попадёт в JSON. Значение `email_verified: true` без email отклоняется локально. Ответ предоставляет `id`, secret-bearing `url` и `DateTimeImmutable $expires_at`.

## Ошибки и повторные попытки

SDK использует `AlwaysThrowOnErrors` и не выполняет скрытых retry. HTTP-ошибки приходят как `Saloon\Exceptions\Request\RequestException`, сетевые сбои — как `Saloon\Exceptions\Request\FatalRequestException`.

```php
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

try {
    $response = $linkado->events()->send($event);
} catch (RequestException $exception) {
    $status = $exception->getStatus();
    $retryAfter = $exception->getResponse()->headers()->get('Retry-After');

    // 409 означает конфликт idempotency и не должен повторяться автоматически.
    // Для 429 соблюдайте Retry-After. 408 и 5xx можно повторять с backoff.
    // Остальные 4xx требуют исправления credential, scope или payload.
} catch (FatalRequestException $exception) {
    // Повторите доставку через очередь с bounded exponential backoff и jitter.
}
```

Классифицируйте `401`, `403`, `409`, `422` и прочие стабильные `4xx` как permanent failure. Повторяйте connection/timeout failures, `408`, `429` и `5xx`, сохраняя тот же `event_id` и тот же payload.

## Надёжная доставка и transactional outbox

SDK выполняет один HTTP-запрос и не реализует delivery policy. Для денежных и lifecycle-событий записывайте событие в transactional outbox в той же транзакции БД, что и бизнес-изменение. Worker должен читать сохранённый payload, повторять его без изменений и фиксировать результат доставки.

Не вызывайте Linkado синхронно внутри checkout/payment transaction: внешний timeout не должен откатывать оплату и не должен создавать дубликаты. `event_id` генерирует приложение один раз при создании outbox-записи; при retry его нельзя менять.

HTTP `200`/`202` подтверждает приём события, но не гарантирует окончательную обработку процессором. Используйте `status`, `result` и `warnings` ответа как текущую диагностику, не как замену собственной учётной записи доставки.

## Staging и тесты

Передавайте выданный Linkado staging URL в `baseUrl`; SDK не подменяет hostname и не переключает окружения автоматически. Тестируйте интеграцию через Saloon `MockClient`, не выполняя реальные HTTP-запросы из test suite.

## Laravel Boost

Пакет содержит skill с правилами безопасной интеграции. После установки SDK в Laravel-приложение импортируйте package skills командой:

```bash
php artisan boost:install --skills
```

## Разработка и релизы

```bash
composer install
composer test
composer pint
```

Изменения документируются в [CHANGELOG.md](CHANGELOG.md), процедура первого и последующих релизов — в [RELEASING.md](RELEASING.md). Лицензия — [MIT](LICENSE.md).
