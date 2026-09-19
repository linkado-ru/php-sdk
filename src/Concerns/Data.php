<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Concerns;

use BackedEnum;
use DateTimeInterface;
use InvalidArgumentException;
use Linkado\PhpSdk\Support\DateTimeNormalizer;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;

abstract class Data
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data = []): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor?->getParameters() ?? [];
        $known = array_map(static fn ($parameter): string => $parameter->getName(), $parameters);

        foreach (array_diff(array_keys($data), $known) as $unknown) {
            throw new InvalidArgumentException("Unknown parameter: {$unknown}");
        }

        $arguments = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $data)) {
                $arguments[$name] = self::normalizeConstructorValue($parameter->getType(), $data[$name]);

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[$name] = $parameter->getDefaultValue();

                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->allowsNull()) {
                $arguments[$name] = null;

                continue;
            }

            throw new InvalidArgumentException("Missing required parameter: {$name}");
        }

        return new static(...$arguments);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_map(
            fn (mixed $value): mixed => $this->normalizeValue($value),
            get_object_vars($this),
        );
    }

    protected static function normalizeConstructorValue(?ReflectionType $type, mixed $value): mixed
    {
        if ($value === null || ! $type instanceof ReflectionNamedType) {
            return $value;
        }

        if ($value instanceof BackedEnum && $type->isBuiltin()) {
            return $value->value;
        }

        if ($type->isBuiltin()) {
            return $value;
        }

        $className = $type->getName();

        if (enum_exists($className) && is_subclass_of($className, BackedEnum::class)) {
            return $value instanceof $className ? $value : $className::from($value);
        }

        if (is_array($value) && is_subclass_of($className, self::class)) {
            return $className::from($value);
        }

        return $value;
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeNormalizer::serialize($value);
        }

        if ($value instanceof self) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->normalizeValue($item),
                $value,
            );
        }

        return $value;
    }
}
