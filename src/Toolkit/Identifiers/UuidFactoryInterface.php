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

use DateTimeInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\UuidInterface;

interface UuidFactoryInterface
{
    /**
     * Create a UUID identifier from an identifier or a base UUID interface.
     *
     * @param IdentifierInterface|UuidInterface $uuid
     * @return Uuid
     */
    public function from(IdentifierInterface|UuidInterface $uuid): Uuid;

    /**
     * Creates a UUID from a byte string
     *
     * @param string $bytes A binary string
     * @return Uuid A UUID instance created from a binary string representation
     */
    public function fromBytes(string $bytes): Uuid;

    /**
     * Creates a UUID from a DateTimeInterface instance
     *
     * @param DateTimeInterface $dateTime The date and time
     * @param Hexadecimal|null $node A 48-bit number representing the hardware
     *     address
     * @param int|null $clockSeq A 14-bit number used to help avoid duplicates
     *     that could arise when the clock is set backwards in time or if the
     *     node ID changes
     * @return Uuid A UUID instance that represents a version 1 UUID created from
     *     a DateTimeInterface instance
     */
    public function fromDateTime(
        DateTimeInterface $dateTime,
        ?Hexadecimal $node = null,
        ?int $clockSeq = null,
    ): Uuid;

    /**
     * Creates a UUID from a 128-bit integer string
     *
     * @param string $integer String representation of 128-bit integer
     * @return Uuid A UUID instance created from the string representation of a
     *      128-bit integer
     */
    public function fromInteger(string $integer): Uuid;

    /**
     * Creates a UUID from the string standard representation
     *
     * @param string $uuid A hexadecimal string
     * @return Uuid A UUID instance created from a hexadecimal string
     *      representation
     */
    public function fromString(string $uuid): Uuid;

    /**
     * Returns a version 1 (Gregorian time) UUID from a host ID, sequence number,
     * and the current time
     *
     * @param Hexadecimal|int|string|null $node A 48-bit number representing the
     *     hardware address; this number may be represented as an integer or a
     *     hexadecimal string
     * @param int|null $clockSeq A 14-bit number used to help avoid duplicates
     *     that could arise when the clock is set backwards in time or if the
     *     node ID changes
     * @return Uuid A UUID instance that represents a version 1 UUID
     */
    public function uuid1(
        Hexadecimal|int|string|null $node = null,
        ?int $clockSeq = null,
    ): Uuid;

    /**
     * Returns a version 2 (DCE Security) UUID from a local domain, local
     * identifier, host ID, clock sequence, and the current time
     *
     * @param int $localDomain The local domain to use when generating bytes,
     *     according to DCE Security
     * @param IntegerObject|null $localIdentifier The local identifier for the
     *     given domain; this may be a UID or GID on POSIX systems, if the local
     *     domain is person or group, or it may be a site-defined identifier
     *     if the local domain is org
     * @param Hexadecimal|null $node A 48-bit number representing the hardware
     *     address
     * @param int|null $clockSeq A 14-bit number used to help avoid duplicates
     *     that could arise when the clock is set backwards in time or if the
     *     node ID changes
     * @return Uuid A UUID instance that represents a version 2 UUID
     */
    public function uuid2(
        int $localDomain,
        ?IntegerObject $localIdentifier = null,
        ?Hexadecimal $node = null,
        ?int $clockSeq = null,
    ): Uuid;

    /**
     * Returns a version 3 (name-based) UUID based on the MD5 hash of a
     * namespace ID and a name
     *
     * @param string|UuidInterface $ns The namespace (must be a valid UUID)
     * @param string $name The name to use for creating a UUID
     * @return Uuid A UUID instance that represents a version 3 UUID
     */
    public function uuid3(UuidInterface|string $ns, string $name): Uuid;

    /**
     * Returns a version 4 (random) UUID
     *
     * @return Uuid A UUID instance that represents a version 4 UUID
     */
    public function uuid4(): Uuid;

    /**
     * Returns a version 5 (name-based) UUID based on the SHA-1 hash of a
     * namespace ID and a name
     *
     * @param string|UuidInterface $ns The namespace (must be a valid UUID)
     * @param string $name The name to use for creating a UUID
     * @return Uuid A UUID instance that represents a version 5 UUID
     */
    public function uuid5(UuidInterface|string $ns, string $name): Uuid;

    /**
     * Returns a version 6 (reordered time) UUID from a host ID, sequence number,
     * and the current time
     *
     * @param Hexadecimal|null $node A 48-bit number representing the hardware
     *     address
     * @param int|null $clockSeq A 14-bit number used to help avoid duplicates
     *     that could arise when the clock is set backwards in time or if the
     *     node ID changes
     * @return Uuid A UUID instance that represents a version 6 UUID
     */
    public function uuid6(?Hexadecimal $node = null, ?int $clockSeq = null): Uuid;
}
