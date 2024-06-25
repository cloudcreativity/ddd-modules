<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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
    /**
     * @var IUuidFactory|null
     */
    private static ?IUuidFactory $factory = null;

    /**
     * @param IUuidFactory|null $factory
     * @return void
     */
    public static function setFactory(?IUuidFactory $factory): void
    {
        self::$factory = $factory;
    }

    /**
     * @return IUuidFactory
     */
    public static function getFactory(): IUuidFactory
    {
        if (self::$factory) {
            return self::$factory;
        }

        return self::$factory = new UuidFactory();
    }

    /**
     * @param Identifier|IBaseUuid|string $value
     * @return self
     */
    public static function from(Identifier|IBaseUuid|string $value): self
    {
        $factory = self::getFactory();

        return match(true) {
            $value instanceof Identifier, $value instanceof IBaseUuid => $factory->from($value),
            is_string($value) => $factory->fromString($value),
        };
    }

    /**
     * Generate a random UUID, useful in tests.
     *
     * @return self
     */
    public static function random(): self
    {
        return self::getFactory()->uuid4();
    }

    /**
     * Create a nil UUID.
     *
     * @return self
     */
    public static function nil(): self
    {
        return self::from(BaseUuid::NIL);
    }

    /**
     * Uuid constructor.
     *
     * @param IBaseUuid $value
     */
    public function __construct(public readonly IBaseUuid $value)
    {
    }


    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->value->toString();
    }

    /**
     * @return string
     */
    public function getBytes(): string
    {
        return $this->value->getBytes();
    }

    /**
     * @inheritDoc
     */
    public function is(?Identifier $other): bool
    {
        if ($other instanceof self) {
            return $this->equals($other);
        }

        return false;
    }

    /**
     * @param Uuid $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->value->toString();
    }

    /**
     * @inheritDoc
     */
    public function context(): string
    {
        return $this->value->toString();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): string
    {
        return $this->value->toString();
    }
}
