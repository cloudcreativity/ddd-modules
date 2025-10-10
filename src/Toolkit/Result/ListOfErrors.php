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
use Deprecated;
use LogicException;
use UnitEnum;

use function CloudCreativity\Modules\Toolkit\enum_string;

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

        trigger_error(
            'Calling first() with a matcher is deprecated and will be removed in 6.0; use find() instead.',
            E_USER_DEPRECATED,
        );

        return $this->find($matcher);
    }

    public function find(Closure|UnitEnum $matcher): ?IError
    {
        return array_find($this->stack, $this->where($matcher));
    }


    public function sole(Closure|UnitEnum|null $matcher = null): IError
    {
        $errors = $matcher ? $this->filter($matcher) : $this;

        if (count($errors->stack) === 1) {
            return $errors->stack[0];
        }

        throw new LogicException(sprintf(
            'Expected exactly one %s but there are %d.',
            match (true) {
                $matcher instanceof UnitEnum => sprintf('error with code "%s"', enum_string($matcher)),
                $matcher instanceof Closure => 'error matching the criteria',
                default => 'error',
            },
            count($errors->stack),
        ));
    }

    #[Deprecated(message: 'use any() instead', since: '5.0.0-rc.2')]
    public function contains(Closure|UnitEnum $matcher): bool
    {
        return $this->any($matcher);
    }

    public function any(Closure|UnitEnum $matcher): bool
    {
        return array_any($this->stack, $this->where($matcher));
    }

    public function every(Closure|UnitEnum $matcher): bool
    {
        return array_all($this->stack, $this->where($matcher));
    }

    public function filter(Closure|UnitEnum $matcher): self
    {
        return new self(...array_values(
            array_filter($this->stack, $this->where($matcher)),
        ));
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

    public function messages(): array
    {
        $messages = [];

        foreach ($this->stack as $error) {
            $message = $error->message();

            if (strlen($message) > 0) {
                $messages[$message] = $message;
            }
        }

        return array_values($messages);
    }

    public function message(): ?string
    {
        foreach ($this->stack as $error) {
            $message = $error->message();

            if (strlen($message) > 0) {
                return $message;
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

    /**
     * @param (Closure(IError): bool)|UnitEnum $matcher
     * @return Closure(IError): bool
     */
    private function where(Closure|UnitEnum $matcher): Closure
    {
        if ($matcher instanceof UnitEnum) {
            return static fn (IError $error): bool => $error->is($matcher);
        }

        return $matcher;
    }
}
