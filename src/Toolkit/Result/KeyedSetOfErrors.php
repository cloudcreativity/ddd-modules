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

    /**
     * @param KeyedSetOfErrors|IListOfErrors|IError $value
     * @return self
     */
    public static function from(self|IListOfErrors|IError $value): self
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
     * @param IError ...$errors
     */
    public function __construct(IError ...$errors)
    {
        foreach ($errors as $error) {
            $key = $this->keyFor($error);
            $this->stack[$key] = $this->get($key)->push($error);
        }

        ksort($this->stack);
    }

    /**
     * Return a new instance with the provided error added to the set of errors.
     *
     * @param IError $error
     * @return KeyedSetOfErrors
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

    /**
     * @param IListOfErrors|self $other
     * @return self
     */
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
     * @param string $key
     * @return IListOfErrors
     */
    public function get(string $key): IListOfErrors
    {
        return $this->stack[$key] ?? new ListOfErrors();
    }

    /**
     * @return IListOfErrors
     */
    public function toList(): IListOfErrors
    {
        return array_reduce(
            $this->stack,
            static fn (IListOfErrors $carry, IListOfErrors $errors): IListOfErrors => $carry->merge($errors),
            new ListOfErrors(),
        );
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->stack;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return array_reduce(
            $this->stack,
            static fn (int $carry, IListOfErrors $errors) => $carry + $errors->count(),
            0,
        );
    }

    /**
     * @param IError $error
     * @return string
     */
    private function keyFor(IError $error): string
    {
        return $error->key() ?? self::DEFAULT_KEY;
    }
}
