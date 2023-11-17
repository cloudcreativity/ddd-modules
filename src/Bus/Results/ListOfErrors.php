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

use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\ListInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\ListTrait;

class ListOfErrors implements ErrorIterableInterface, ListInterface
{
    use ListTrait;

    /**
     * @var ErrorInterface[]
     */
    private array $stack;

    /**
     * @param ErrorInterface ...$errors
     */
    public function __construct(ErrorInterface ...$errors)
    {
        $this->stack = $errors;
    }

    /**
     * Get the first error.
     *
     * @return ErrorInterface|null
     */
    public function first(): ?ErrorInterface
    {
        return $this->stack[0] ?? null;
    }

    /**
     * Return a new instance with the provided error pushed on to the end of the stack.
     *
     * @param ErrorInterface $error
     * @return ListOfErrors
     */
    public function push(ErrorInterface $error): self
    {
        $copy = clone $this;
        $copy->stack[] = $error;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function merge(ErrorIterableInterface $other): self
    {
        $copy = clone $this;
        $copy->stack = array_merge($copy->stack, $other->all());

        return $copy;
    }

    /**
     * @return ErrorInterface[]
     */
    public function all(): array
    {
        return $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function toList(): ListOfErrors
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toKeyedSet(): KeyedSetOfErrors
    {
        return new KeyedSetOfErrors(...$this->stack);
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        return array_map(
            static fn(ErrorInterface $error) => $error->context(),
            $this->stack,
        );
    }
}
