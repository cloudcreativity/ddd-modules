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
use Generator;
use ReflectionClass;
use ReflectionProperty;

final class ObjectContext implements ContextProvider
{
    /**
     * @param object $source
     * @return self
     */
    public static function from(object $source): self
    {
        return new self($source);
    }

    /**
     * ObjectContext constructor.
     *
     * @param object $source
     */
    public function __construct(private readonly object $source)
    {
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        if ($this->source instanceof ContextProvider) {
            return $this->source->context();
        }

        $values = [];

        foreach ($this->keys() as $key) {
            $values[$key] = $this->source->{$key};
        }

        return $values;
    }

    /**
     * @return Generator<string>
     */
    private function keys(): Generator
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
