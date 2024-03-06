<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use CloudCreativity\Modules\Toolkit\Iterables\KeyedSetInterface;
use CloudCreativity\Modules\Toolkit\Iterables\KeyedSetTrait;

/**
 * @implements KeyedSetInterface<ListOfErrors>
 */
final class KeyedSetOfErrors implements KeyedSetInterface
{
    /** @use KeyedSetTrait<ListOfErrors> */
    use KeyedSetTrait;

    /**
     * @var string
     */
    public const DEFAULT_KEY = '_base';

    /**
     * @param KeyedSetOfErrors|ListOfErrorsInterface|ErrorInterface $value
     * @return self
     */
    public static function from(self|ListOfErrorsInterface|ErrorInterface $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            $value instanceof ListOfErrorsInterface => new self(...$value),
            $value instanceof ErrorInterface => new self($value),
        };
    }

    /**
     * KeyedSetOfErrors constructor.
     *
     * @param ErrorInterface ...$errors
     */
    public function __construct(ErrorInterface ...$errors)
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
     * @param ErrorInterface $error
     * @return KeyedSetOfErrors
     */
    public function put(ErrorInterface $error): self
    {
        $key = $this->keyFor($error);
        $errors = $this->get($key);

        $copy = clone $this;
        $copy->stack[$key] = $errors->push($error);

        ksort($copy->stack);

        return $copy;
    }

    /**
     * @param ListOfErrorsInterface|self $other
     * @return self
     */
    public function merge(ListOfErrorsInterface|self $other): self
    {
        $copy = clone $this;

        foreach (self::from($other) as $key => $errors) {
            assert(is_string($key) && $errors instanceof ListOfErrorsInterface);
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
     * @return ListOfErrors
     */
    public function get(string $key): ListOfErrors
    {
        return $this->stack[$key] ?? new ListOfErrors();
    }

    /**
     * @return ListOfErrors
     */
    public function toList(): ListOfErrors
    {
        return array_reduce(
            $this->stack,
            static fn (ListOfErrors $carry, ListOfErrors $errors): ListOfErrors => $carry->merge($errors),
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
            static fn (int $carry, ListOfErrors $errors) => $carry + $errors->count(),
            0,
        );
    }

    /**
     * @param ErrorInterface $error
     * @return string
     */
    private function keyFor(ErrorInterface $error): string
    {
        return $error->key() ?? self::DEFAULT_KEY;
    }
}
