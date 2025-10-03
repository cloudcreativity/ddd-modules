<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\UuidFactory as IUuidFactory;
use JsonSerializable;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidInterface as IBaseUuid;

final class Uuid implements Identifier, JsonSerializable
{
    use IsIdentifier;

    private static ?IUuidFactory $factory = null;

    public static function setFactory(?IUuidFactory $factory): void
    {
        self::$factory = $factory;
    }

    public static function getFactory(): IUuidFactory
    {
        if (self::$factory) {
            return self::$factory;
        }

        return self::$factory = new UuidFactory();
    }

    public static function from(IBaseUuid|Identifier|string $value): self
    {
        $factory = self::getFactory();

        return match(true) {
            $value instanceof Identifier, $value instanceof IBaseUuid => $factory->from($value),
            is_string($value) => $factory->fromString($value),
        };
    }

    public static function tryFrom(IBaseUuid|Identifier|string|null $value): ?self
    {
        $factory = self::getFactory();

        return match(true) {
            $value instanceof self => $value,
            $value instanceof IBaseUuid => $factory->from($value),
            is_string($value) && BaseUuid::isValid($value) => $factory->fromString($value),
            default => null,
        };
    }

    /**
     * Generate a random UUID, useful in tests.
     */
    public static function random(): self
    {
        return self::getFactory()->uuid4();
    }

    /**
     * Create a nil UUID.
     */
    public static function nil(): self
    {
        return self::from(BaseUuid::NIL);
    }

    public function __construct(public readonly IBaseUuid $value)
    {
    }


    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function getBytes(): string
    {
        return $this->value->getBytes();
    }

    public function is(?Identifier $other): bool
    {
        if ($other instanceof self) {
            return $this->equals($other);
        }

        return false;
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function key(): string
    {
        return $this->value->toString();
    }

    public function context(): string
    {
        return $this->value->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->value->toString();
    }
}
