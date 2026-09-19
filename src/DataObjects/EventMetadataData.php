<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use InvalidArgumentException;
use JsonException;
use Linkado\PhpSdk\Concerns\Data;

final class EventMetadataData extends Data
{
    public function __construct(
        public readonly string|int|float|bool|null $source = null,
        public readonly string|int|float|bool|null $source_event = null,
        public readonly string|int|float|bool|null $plan_code = null,
        public readonly string|int|float|bool|null $billing_reason = null,
        public readonly string|int|float|bool|null $checkout_session_id = null,
    ) {
        $this->validate();
    }

    /** @return array<string, string|int|float|bool> */
    public function toArray(): array
    {
        return array_filter(
            parent::toArray(),
            static fn (mixed $value): bool => $value !== null,
        );
    }

    private function validate(): void
    {
        foreach ($this->toArray() as $key => $value) {
            if ($this->containsSensitiveValue((string) $value)) {
                throw new InvalidArgumentException("Event metadata [{$key}] must not contain credentials or PII.");
            }
        }

        try {
            $encoded = json_encode(
                $this->canonicalize($this->toArray()),
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Event metadata must be JSON serializable.', previous: $exception);
        }

        if (strlen($encoded) > 4096) {
            throw new InvalidArgumentException('Event metadata must not exceed 4096 canonical JSON bytes.');
        }
    }

    /** @param array<string, string|int|float|bool> $values */
    private function canonicalize(array $values): array
    {
        ksort($values, SORT_STRING);

        return $values;
    }

    private function containsSensitiveValue(string $value): bool
    {
        $trimmed = trim($value);
        $compact = preg_replace('/\s+/', '', $trimmed) ?? $trimmed;

        if (filter_var($trimmed, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $trimmed) === 1
            || preg_match('/\A(?:Bearer|Basic)\s+\S+/i', $trimmed) === 1
            || preg_match('/\A[A-Z]{2}\d{2}[A-Z0-9]{11,30}\z/i', $compact) === 1) {
            return true;
        }

        preg_match_all('/(?<![A-Z0-9])(?:\+?\d|\(\d)[\d\s().-]{7,30}\d(?![A-Z0-9])/i', $trimmed, $matches);

        foreach ($matches[0] as $candidate) {
            $digits = preg_replace('/\D/', '', $candidate) ?? '';

            if (strlen($digits) >= 10 && strlen($digits) <= 19) {
                return true;
            }
        }

        return false;
    }
}
