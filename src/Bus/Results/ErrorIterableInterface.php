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

namespace CloudCreativity\BalancedEvent\Common\Bus\Results;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Log\ContextProviderInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\IterableInterface;

interface ErrorIterableInterface extends IterableInterface, ContextProviderInterface
{
    /**
     * Return a new instance with the provided errors merged in.
     *
     * @param ErrorIterableInterface $other
     * @return ErrorIterableInterface
     */
    public function merge(ErrorIterableInterface $other): self;

    /**
     * @return ListOfErrors
     */
    public function toList(): ListOfErrors;

    /**
     * @return KeyedSetOfErrors
     */
    public function toKeyedSet(): KeyedSetOfErrors;

    /**
     * @return ErrorInterface[]
     */
    public function all(): array;
}
