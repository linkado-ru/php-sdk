<?php

declare(strict_types=1);

use Linkado\PhpSdk\Tests\TestCase;
use Saloon\Config;
use Saloon\Http\Faking\MockClient;

uses(TestCase::class)->in('.');

uses()
    ->beforeEach(fn () => MockClient::destroyGlobal())
    ->in(__DIR__);

Config::preventStrayRequests();
