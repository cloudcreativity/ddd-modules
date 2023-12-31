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

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

final class Uuid implements IdentifierInterface, JsonSerializable
{
    /**
     * @var UuidFactoryInterface|null
     */
    private static ?UuidFactoryInterface $factory = null;

    /**
     * @param UuidFactoryInterface|null $factory
     * @return void
     */
    public static function setFactory(?UuidFactoryInterface $factory): void
    {
        self::$factory = $factory;
    }

    /**
     * @return UuidFactoryInterface
     */
    public static function getFactory(): UuidFactoryInterface
    {
        if (self::$factory) {
            return self::$factory;
        }

        return self::$factory = new UuidFactory();
    }

    /**
     * @param IdentifierInterface|UuidInterface|string $value
     * @return self
     */
    public static function from(IdentifierInterface|UuidInterface|string $value): self
    {
        $factory = self::getFactory();

        return match(true) {
            $value instanceof IdentifierInterface, $value instanceof UuidInterface => $factory->from($value),
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
     * Uuid constructor.
     *
     * @param UuidInterface $value
     */
    public function __construct(public readonly UuidInterface $value)
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
     * @inheritDoc
     */
    public function is(?IdentifierInterface $other): bool
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
