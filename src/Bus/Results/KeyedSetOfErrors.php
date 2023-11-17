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

use CloudCreativity\Modules\Toolkit\Iterables\KeyedSetInterface;
use CloudCreativity\Modules\Toolkit\Iterables\KeyedSetTrait;

final class KeyedSetOfErrors implements ErrorIterableInterface, KeyedSetInterface
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
