<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Log;

use BackedEnum;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\IdentifierInterface;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;
use Stringable;

final class Context
{
    /**
     * Parse unknown values to log context.
     *
     * This should only be used for parsing values that are not known to the
     * class that is implementing the context provider interface.
     *
     * If a class understands the values it has, it should convert them manually
     * to log context - as this will be more efficient than using this method.
     *
     * @param iterable $input
     * @return array<array-key, mixed>
     */
    public static function parse(iterable $input): array
    {
        $parsed = [];

        foreach ($input as $key => $value) {
            $parsed[$key] = match(true) {
                $value instanceof ContextProviderInterface => $value->context(),
                $value instanceof IdentifierInterface => $value->context(),
                $value instanceof BackedEnum => $value->value,
                $value instanceof UuidInterface => $value->toString(),
                $value instanceof DateTimeInterface => $value->format('Y-m-d\TH:i:s.up'),
                is_iterable($value) => self::parse($value),
                $value instanceof Stringable => (string) $value,
                default => $value,
            };
        }

        return $parsed;
    }

    /**
     * Context constructor.
     */
    private function __construct()
    {
        // no-op
    }
}