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

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers;

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
use CloudCreativity\BalancedEvent\Common\Toolkit\Contracts;

final readonly class Guid implements IdentifierInterface
{
    /**
     * @param IdentifierInterface $value
     * @return self
     */
    public static function from(IdentifierInterface $value): self
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
     * @param IdentifierInterface $id
     */
    public function __construct(
        public string $type,
        public IdentifierInterface $id,
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
    public function is(?IdentifierInterface $other): bool
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
     * @inheritDoc
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
