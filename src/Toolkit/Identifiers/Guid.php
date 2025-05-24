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
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Contracts;
use Ramsey\Uuid\UuidInterface;
use UnitEnum;

use function CloudCreativity\Modules\Toolkit\enum_string;
use function CloudCreativity\Modules\Toolkit\enum_value;

final class Guid implements Identifier
{
    /**
     * @param Identifier $value
     * @return self
     */
    public static function from(Identifier $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        throw new ContractException('Unexpected identifier type, received: ' . get_debug_type($value));
    }

    /**
     * Create a GUID with an integer id.
     *
     * @param UnitEnum|string $type
     * @param int $id
     * @return self
     */
    public static function fromInteger(UnitEnum|string $type, int $id): self
    {
        return new self($type, new IntegerId($id));
    }

    /**
     * Create a GUID with a string id.
     *
     * @param UnitEnum|string $type
     * @param string $id
     * @return self
     */
    public static function fromString(UnitEnum|string $type, string $id): self
    {
        return new self($type, new StringId($id));
    }

    /**
     * Create a GUID for a UUID.
     *
     * @param UnitEnum|string $type
     * @param UuidInterface|string $uuid
     * @return self
     */
    public static function fromUuid(UnitEnum|string $type, UuidInterface|string $uuid): self
    {
        return new self($type, Uuid::from($uuid));
    }

    /**
     * Create a GUID.
     *
     * @param UnitEnum|string $type
     * @param string|int $id
     * @return self
     */
    public static function make(UnitEnum|string $type, string|int $id): self
    {
        if (is_int($id)) {
            return self::fromInteger($type, $id);
        }

        return self::fromString($type, $id);
    }

    /**
     * Guid constructor.
     *
     * @param UnitEnum|string $type
     * @param StringId|IntegerId|Uuid $id
     */
    public function __construct(
        public readonly UnitEnum|string $type,
        public readonly StringId|IntegerId|Uuid $id,
    ) {
        Contracts::assert(!empty($this->type), 'Type must be a non-empty string.');
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @param UnitEnum|string ...$types
     * @return bool
     */
    public function isType(UnitEnum|string ...$types): bool
    {
        foreach ($types as $type) {
            if ($this->type === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function is(?Identifier $other): bool
    {
        if ($this === $other) {
            return true;
        }

        if ($other instanceof self) {
            return
                $this->isType($other->type) &&
                $this->id->is($other->id);
        }

        return false;
    }

    /**
     * @param Guid $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->is($other);
    }

    /**
     * Fluent to-string method.
     *
     * @param string $glue
     * @return string
     */
    public function toString(string $glue = ':'): string
    {
        $type = enum_string($this->type);

        return "{$type}{$glue}{$this->id->value}";
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->toString();
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'type' => enum_string($this->type),
            'id' => $this->id->context(),
        ];
    }

    /**
     * Assert that this GUID is of the expected type.
     *
     * @param UnitEnum|string $expected
     * @param string $message
     * @return $this
     */
    public function assertType(UnitEnum|string $expected, string $message = ''): self
    {
        Contracts::assert($this->type === $expected, $message ?: fn () => sprintf(
            'Expecting type "%s", received "%s".',
            enum_string($expected),
            enum_string($this->type),
        ));

        return $this;
    }

    /**
     * Get the type expressed as a string or an integer.
     *
     * @return string|int
     */
    public function type(): string|int
    {
        // TODO 4.0 use enum_string() instead
        return enum_value($this->type);
    }
}
