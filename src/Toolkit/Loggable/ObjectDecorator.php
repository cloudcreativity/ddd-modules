<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Loggable;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\Contextual;
use Generator;
use IteratorAggregate;
use Ramsey\Uuid\UuidInterface;
use ReflectionClass;
use ReflectionProperty;
use UnitEnum;

use function CloudCreativity\Modules\Toolkit\enum_string;

/**
 * @implements IteratorAggregate<string, mixed>
 */
final readonly class ObjectDecorator implements IteratorAggregate, ContextProvider
{
    public function __construct(private object $source)
    {
    }

    /**
     * @return Generator<string, mixed>
     */
    public function getIterator(): Generator
    {
        foreach ($this->cursor() as $key) {
            $value = $this->source->{$key};
            yield $key => match (true) {
                $value instanceof ContextProvider => $value->context(),
                $value instanceof Contextual => $value->context(),
                $value instanceof UuidInterface => $value->toString(),
                $value instanceof UnitEnum => enum_string($value),
                default => $value,
            };
        }
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return iterator_to_array($this->cursor());
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return iterator_to_array($this);
    }

    public function context(): array
    {
        return $this->all();
    }

    /**
     * @return Generator<string>
     */
    private function cursor(): Generator
    {
        $reflect = new ReflectionClass($this->source);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes(Sensitive::class);
            if (count($attributes) === 0) {
                yield $property->getName();
            }
        }
    }
}
