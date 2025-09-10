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

use CloudCreativity\Modules\Contracts\Toolkit\Iterables\KeyedSet;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Iterables\IsKeyedSet;
use UnitEnum;

use function CloudCreativity\Modules\Toolkit\enum_string;

/**
 * @implements KeyedSet<IListOfErrors>
 */
final class KeyedSetOfErrors implements KeyedSet
{
    /** @use IsKeyedSet<IListOfErrors> */
    use IsKeyedSet;

    /**
     * @var string
     */
    public const DEFAULT_KEY = '_base';

    public static function from(IError|IListOfErrors|self $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            $value instanceof IListOfErrors => new self(...$value),
            $value instanceof IError => new self($value),
        };
    }

    /**
     * KeyedSetOfErrors constructor.
     *
     */
    public function __construct(IError ...$errors)
    {
        foreach ($errors as $error) {
            $key = $this->keyFor($error);
            $this->stack[$key] = $this->get($key)->push($error);
        }

        ksort($this->stack, SORT_STRING | SORT_FLAG_CASE);
    }

    /**
     * Return a new instance with the provided error added to the set of errors.
     *
     */
    public function put(IError $error): self
    {
        $key = $this->keyFor($error);
        $errors = $this->get($key);

        $copy = clone $this;
        $copy->stack[$key] = $errors->push($error);

        ksort($copy->stack);

        return $copy;
    }

    public function merge(IListOfErrors|self $other): self
    {
        $copy = clone $this;

        foreach (self::from($other) as $key => $errors) {
            $copy->stack[$key] = $copy->get($key)->merge($errors);
        }

        ksort($copy->stack);

        return $copy;
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->stack);
    }

    /**
     * Get errors by key.
     *
     */
    public function get(string|UnitEnum $key): IListOfErrors
    {
        $key = enum_string($key);

        return $this->stack[$key] ?? new ListOfErrors();
    }

    public function toList(): IListOfErrors
    {
        return array_reduce(
            $this->stack,
            static fn (IListOfErrors $carry, IListOfErrors $errors): IListOfErrors => $carry->merge($errors),
            new ListOfErrors(),
        );
    }

    public function all(): array
    {
        return $this->stack;
    }

    public function count(): int
    {
        $count = array_reduce(
            $this->stack,
            static fn (int $carry, IListOfErrors $errors) => $carry + $errors->count(),
            0,
        );

        assert($count >= 0, 'Expecting count to be zero or greater.');

        return $count;
    }

    private function keyFor(IError $error): string
    {
        $key = $error->key();

        return $key === null ? self::DEFAULT_KEY : enum_string($key);
    }
}
