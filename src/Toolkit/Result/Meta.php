<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use ArrayAccess;
use CloudCreativity\Modules\Contracts\Toolkit\Iterables\KeyedSet;
use CloudCreativity\Modules\Toolkit\Iterables\IsKeyedSet;
use LogicException;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements KeyedSet<mixed>
 */
final class Meta implements ArrayAccess, KeyedSet
{
    /** @use IsKeyedSet<mixed> */
    use IsKeyedSet;

    /**
     * Cast a value to meta.
     *
     * @param Meta|array<string, mixed> $values
     * @return Meta
     */
    public static function cast(self|array $values): self
    {
        if ($values instanceof self) {
            return $values;
        }

        return new self($values);
    }

    /**
     * ResultMeta constructor.
     *
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        assert(empty($values) || !array_is_list($values), 'Expecting meta to be a keyed array, not an array list.');

        $this->stack = $values;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->exists($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new LogicException('Result meta is immutable.');
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): never
    {
        throw new LogicException('Result meta is immutable.');
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->exists($key)) {
            return $this->stack[$key];
        }

        return $default;
    }

    /**
     * Does a value for the provided key exist?
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->stack);
    }

    /**
     * Put a value into the meta.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function put(string $key, mixed $value): self
    {
        $copy = clone $this;
        $copy->stack[$key] = $value;

        return $copy;
    }

    /**
     * Merge values into the meta.
     *
     * @param self|array<string, mixed> $values
     * @return $this
     */
    public function merge(self|array $values): self
    {
        if ($values instanceof self) {
            $values = $values->stack;
        }

        $copy = clone $this;
        $copy->stack = array_merge($this->stack, $values);

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->stack;
    }
}
