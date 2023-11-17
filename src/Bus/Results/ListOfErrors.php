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

namespace CloudCreativity\Modules\Bus\Results;

use CloudCreativity\Modules\Toolkit\Iterables\ListInterface;
use CloudCreativity\Modules\Toolkit\Iterables\ListTrait;

final class ListOfErrors implements ErrorIterableInterface, ListInterface
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
