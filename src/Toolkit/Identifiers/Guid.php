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
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface;
use UnitEnum;

use function CloudCreativity\Modules\Toolkit\enum_string;

final readonly class Guid implements Identifier
{
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
     */
    public static function fromInteger(string|UnitEnum $type, int $id): self
    {
        return new self($type, new IntegerId($id));
    }

    /**
     * Create a GUID with a string id.
     *
     */
    public static function fromString(string|UnitEnum $type, string $id): self
    {
        return new self($type, new StringId($id));
    }

    /**
     * Create a GUID for a UUID.
     *
     */
    public static function fromUuid(string|UnitEnum $type, string|Uuid|UuidInterface $uuid): self
    {
        return new self($type, Uuid::from($uuid));
    }

    /**
     * Create a GUID.
     *
     */
    public static function make(string|UnitEnum $type, int|string|Uuid|UuidInterface $id): self
    {
        return match (true) {
            $id instanceof Uuid, $id instanceof UuidInterface, is_string($id) && RamseyUuid::isValid($id)
            => self::fromUuid($type, $id),
            is_string($id) => self::fromString($type, $id),
            is_int($id) => self::fromInteger($type, $id),
        };
    }

    /**
     * Guid constructor.
     *
     */
    public function __construct(
        public string|UnitEnum $type,
        public IntegerId|StringId|Uuid $id,
    ) {
        Contracts::assert(!empty($this->type), 'Type must be a non-empty string.');
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function isType(string|UnitEnum ...$types): bool
    {
        foreach ($types as $type) {
            if ($this->type === $type) {
                return true;
            }
        }

        return false;
    }

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

    public function equals(self $other): bool
    {
        return $this->is($other);
    }

    /**
     * Fluent to-string method.
     *
     */
    public function toString(string $glue = ':'): string
    {
        $type = enum_string($this->type);

        return "{$type}{$glue}{$this->id->value}";
    }

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
     * @return $this
     */
    public function assertType(string|UnitEnum $expected, string $message = ''): self
    {
        Contracts::assert($this->type === $expected, $message ?: fn () => sprintf(
            'Expecting type "%s", received "%s".',
            enum_string($expected),
            enum_string($this->type),
        ));

        return $this;
    }
}
