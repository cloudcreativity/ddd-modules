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
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Contracts;
use Ramsey\Uuid\UuidInterface;

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
     * @param string $type
     * @param int $id
     * @return static
     */
    public static function fromInteger(string $type, int $id): self
    {
        return new self($type, new IntegerId($id));
    }

    /**
     * Create a GUID with a string id.
     *
     * @param string $type
     * @param string $id
     * @return static
     */
    public static function fromString(string $type, string $id): self
    {
        return new self($type, new StringId($id));
    }

    /**
     * Create a GUID for a UUID.
     *
     * @param string $type
     * @param UuidInterface|string $uuid
     * @return self
     */
    public static function fromUuid(string $type, UuidInterface|string $uuid): self
    {
        return new self($type, Uuid::from($uuid));
    }

    /**
     * Create a GUID.
     *
     * @param string $type
     * @param string|int $id
     * @return self
     */
    public static function make(string $type, string|int $id): self
    {
        if (is_int($id)) {
            return self::fromInteger($type, $id);
        }

        return self::fromString($type, $id);
    }

    /**
     * Guid constructor.
     *
     * @param string $type
     * @param StringId|IntegerId|Uuid $id
     */
    public function __construct(
        public readonly string $type,
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
     * @param string $type
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->type === $type;
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
        return "{$this->type}{$glue}{$this->id->value}";
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
            'type' => $this->type,
            'id' => $this->id->context(),
        ];
    }

    /**
     * Assert that this GUID is of the expected type.
     *
     * @param string $expected
     * @param string $message
     * @return $this
     */
    public function assertType(string $expected, string $message = ''): self
    {
        Contracts::assert($this->type === $expected, $message ?: sprintf(
            'Expecting type "%s", received "%s".',
            $expected,
            $this->type,
        ));

        return $this;
    }
}
