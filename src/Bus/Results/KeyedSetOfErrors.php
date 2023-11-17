<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus\Results;

use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\KeyedSetInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\KeyedSetTrait;

class KeyedSetOfErrors implements ErrorIterableInterface, KeyedSetInterface
{
    use KeyedSetTrait;

    /**
     * @var string
     */
    public const DEFAULT_KEY = '_base';

    /**
     * @var array<string,ListOfErrors>
     */
    private array $stack = [];

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
     * @inheritDoc
     */
    public function merge(ErrorIterableInterface $other): self
    {
        $copy = clone $this;

        foreach ($other->toKeyedSet() as $key => $errors) {
            $copy->stack[$key] = $copy->get($key)->merge($errors);
        }

        ksort($copy->stack);

        return $copy;
    }

    /**
     * @return array
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
     * @inheritDoc
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
    public function toKeyedSet(): KeyedSetOfErrors
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->toList()->all();
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return array_reduce(
            $this->stack,
            static fn(int $carry, ListOfErrors $errors) => $carry + $errors->count(),
            0,
        );
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        return array_map(
            static fn(ListOfErrors $errors) => $errors->context(),
            $this->stack,
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
