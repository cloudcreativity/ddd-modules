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

use BackedEnum;
use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Iterables\IsList;

final class ListOfErrors implements IListOfErrors
{
    /** @use IsList<IError> */
    use IsList;

    /**
     * @param IListOfErrors|IError|BackedEnum|array<IError>|string $value
     * @return self
     */
    public static function from(IListOfErrors|IError|BackedEnum|array|string $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            $value instanceof IListOfErrors, is_array($value) => new self(...$value),
            $value instanceof IError => new self($value),
            is_string($value) => new self(new Error(message: $value)),
            $value instanceof BackedEnum => new self(new Error(code: $value)),
        };
    }

    /**
     * @param IError ...$errors
     */
    public function __construct(IError ...$errors)
    {
        $this->stack = array_values($errors);
    }

    /**
     * @inheritDoc
     */
    public function first(Closure|BackedEnum|null $matcher = null): ?IError
    {
        if ($matcher === null) {
            return $this->stack[0] ?? null;
        }

        if ($matcher instanceof BackedEnum) {
            $matcher = static fn (IError $error): bool => $error->is($matcher);
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
            $matcher = static fn (IError $error): bool => $error->is($matcher);
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
    public function push(IError $error): self
    {
        $copy = clone $this;
        $copy->stack[] = $error;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function merge(IListOfErrors $other): self
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
