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

namespace CloudCreativity\Modules\Toolkit\Result;

use BackedEnum;
use Closure;
use CloudCreativity\Modules\Toolkit\Iterables\ListTrait;

final class ListOfErrors implements ListOfErrorsInterface
{
    /** @use ListTrait<ErrorInterface> */
    use ListTrait;

    /**
     * @param ListOfErrorsInterface|ErrorInterface|BackedEnum|array<ErrorInterface>|string $value
     * @return self
     */
    public static function from(ListOfErrorsInterface|ErrorInterface|BackedEnum|array|string $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            $value instanceof ListOfErrorsInterface, is_array($value) => new self(...$value),
            $value instanceof ErrorInterface => new self($value),
            is_string($value) => new self(new Error(message: $value)),
            $value instanceof BackedEnum => new self(new Error(code: $value)),
        };
    }

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
    public function first(Closure|BackedEnum|null $matcher = null): ?ErrorInterface
    {
        if ($matcher === null) {
            return $this->stack[0] ?? null;
        }

        if ($matcher instanceof BackedEnum) {
            $matcher = static fn (ErrorInterface $error): bool => $error->is($matcher);
        }

        foreach ($this->stack as $error) {
            if ($matcher($error)) {
                return $error;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function contains(Closure|BackedEnum $matcher): bool
    {
        if ($matcher instanceof BackedEnum) {
            $matcher = static fn (ErrorInterface $error): bool => $error->is($matcher);
        }

        foreach ($this->stack as $error) {
            if ($matcher($error)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function codes(): array
    {
        $codes = [];

        foreach ($this->stack as $error) {
            $code = $error->code();

            if ($code && !in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
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
}
