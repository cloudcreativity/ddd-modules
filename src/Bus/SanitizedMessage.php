<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Contextual;
use CloudCreativity\Modules\Toolkit\Sensitive;
use Generator;
use IteratorAggregate;
use ReflectionClass;
use ReflectionProperty;

/**
 * @implements IteratorAggregate<string, mixed>
 */
final readonly class SanitizedMessage implements IteratorAggregate, Contextual
{
    public function __construct(private Message $message)
    {
    }

    /**
     * @return Generator<string, mixed>
     */
    public function getIterator(): Generator
    {
        foreach ($this->cursor() as $key) {
            yield $key => $this->message->{$key};
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @return Generator<string>
     */
    private function cursor(): Generator
    {
        $reflect = new ReflectionClass($this->message);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes(Sensitive::class);
            if (count($attributes) === 0) {
                yield $property->getName();
            }
        }
    }
}
