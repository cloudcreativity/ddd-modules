<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Log;

use BackedEnum;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
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
