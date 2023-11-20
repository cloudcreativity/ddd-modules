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

use CloudCreativity\Modules\Toolkit\Iterables\ListTrait;

final class ListOfErrors implements ListOfErrorsInterface
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
     * @inheritDoc
     */
    public function first(): ?ErrorInterface
    {
        return $this->stack[0] ?? null;
    }

    /**
     * @inheritDoc
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
    public function merge(ListOfErrorsInterface $other): self
    {
        $copy = clone $this;
        $copy->stack = [
            ...$copy->stack,
            ...$other,
        ];

        return $copy;
    }

    /**
     * @return KeyedSetOfErrors
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
            static fn (ErrorInterface $error) => $error->context(),
            $this->stack,
        );
    }
}
