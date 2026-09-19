<?php

declare(strict_types=1);

use Linkado\PhpSdk\DataObjects\CreateSsoLinkData;
use Linkado\PhpSdk\DataObjects\CustomerCreatedEventData;
use Linkado\PhpSdk\DataObjects\PaymentSucceededEventData;
use Linkado\PhpSdk\Enums\PaymentKind;
use Linkado\PhpSdk\Enums\SsoRedirect;

it('locks the initial public DTO positional constructor order', function (): void {
    $occurredAt = new DateTimeImmutable('2026-08-06T10:00:00Z');

    $customer = new CustomerCreatedEventData(
        '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program-public-key',
        $occurredAt,
        'customer-123',
        'click-123',
        null,
        null,
    );
    $payment = new PaymentSucceededEventData(
        '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program-public-key',
        $occurredAt,
        'customer-123',
        'invoice-100',
        159900,
        'RUB',
        PaymentKind::SubscriptionInitial,
        'subscription-10',
        null,
    );
    $sso = new CreateSsoLinkData(
        'program-public-key',
        'hey-user-123',
        null,
        false,
        'Partner Name',
        SsoRedirect::AffiliatePortal,
    );

    expect($customer->click_id)->toBe('click-123')
        ->and($payment->external_payment_id)->toBe('invoice-100')
        ->and($payment->payment_kind)->toBe(PaymentKind::SubscriptionInitial)
        ->and($sso->redirect_to)->toBe(SsoRedirect::AffiliatePortal);
});

it('keeps initial public DTO parameter names compatible with named arguments', function (): void {
    $customer = new CustomerCreatedEventData(
        event_id: '01K5G6XYBN7QPF9G0AJM1T2E3R',
        program_key: 'program-public-key',
        occurred_at: '2026-08-06T10:00:00Z',
        external_customer_id: 'customer-123',
        referral_slug: 'partner-one',
    );
    $payment = new PaymentSucceededEventData(
        event_id: '01K5G6XYBN7QPF9G0AJM1T2E3R',
        program_key: 'program-public-key',
        occurred_at: '2026-08-06T10:00:00Z',
        external_customer_id: 'customer-123',
        external_payment_id: 'invoice-100',
        amount_minor: 159900,
        currency: 'RUB',
        payment_kind: PaymentKind::OneTime,
    );
    $sso = new CreateSsoLinkData(
        program_key: 'program-public-key',
        external_user_id: 'hey-user-123',
        email: null,
        email_verified: false,
        display_name: 'Partner Name',
        redirect_to: SsoRedirect::AffiliatePortal,
    );

    expect($customer->referral_slug)->toBe('partner-one')
        ->and($payment->payment_kind)->toBe(PaymentKind::OneTime)
        ->and($sso->external_user_id)->toBe('hey-user-123');
});
