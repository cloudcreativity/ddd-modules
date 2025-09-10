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

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Iterables\IsList;
use UnitEnum;

final class ListOfErrors implements IListOfErrors
{
    /** @use IsList<IError> */
    use IsList;

    /**
     * @param array<IError>|IError|IListOfErrors|string|UnitEnum $value
     */
    public static function from(array|IError|IListOfErrors|string|UnitEnum $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            $value instanceof IListOfErrors, is_array($value) => new self(...$value),
            $value instanceof IError => new self($value),
            is_string($value) => new self(new Error(message: $value)),
            $value instanceof UnitEnum => new self(new Error(code: $value)),
        };
    }

    public function __construct(IError ...$errors)
    {
        $this->stack = array_values($errors);
    }

    public function first(Closure|UnitEnum|null $matcher = null): ?IError
    {
        if ($matcher === null) {
            return $this->stack[0] ?? null;
        }

        if ($matcher instanceof UnitEnum) {
            $matcher = static fn (IError $error): bool => $error->is($matcher);
        }

        foreach ($this->stack as $error) {
            if ($matcher($error)) {
                return $error;
            }
        }

        return null;
    }

    public function contains(Closure|UnitEnum $matcher): bool
    {
        if ($matcher instanceof UnitEnum) {
            $matcher = static fn (IError $error): bool => $error->is($matcher);
        }

        foreach ($this->stack as $error) {
            if ($matcher($error)) {
                return true;
            }
        }

        return false;
    }

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

    public function code(): ?UnitEnum
    {
        foreach ($this->stack as $error) {
            if ($code = $error->code()) {
                return $code;
            }
        }

        return null;
    }

    public function push(IError $error): self
    {
        $copy = clone $this;
        $copy->stack[] = $error;

        return $copy;
    }

    public function merge(IListOfErrors $other): self
    {
        $copy = clone $this;
        $copy->stack = [
            ...$copy->stack,
            ...$other,
        ];

        return $copy;
    }

    public function toKeyedSet(): KeyedSetOfErrors
    {
        return new KeyedSetOfErrors(...$this->stack);
    }
}
