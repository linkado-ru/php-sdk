<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class DateTimeNormalizer
{
    public static function parse(DateTimeInterface|string $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return self::immutable($value)->setTimezone(self::utc());
        }

        return (new DateTimeImmutable($value, self::utc()))->setTimezone(self::utc());
    }

    public static function serialize(DateTimeInterface $value): string
    {
        return self::immutable($value)
            ->setTimezone(self::utc())
            ->format(DateTimeInterface::ATOM);
    }

    private static function immutable(DateTimeInterface $value): DateTimeImmutable
    {
        return $value instanceof DateTimeImmutable
            ? $value
            : DateTimeImmutable::createFromInterface($value);
    }

    private static function utc(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }
}
