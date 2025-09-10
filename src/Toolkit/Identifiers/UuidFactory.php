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
use CloudCreativity\Modules\Toolkit\ContractException;
use DateTimeInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidFactoryInterface as BaseUuidFactory;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

final readonly class UuidFactory implements IUuidFactory
{
    private BaseUuidFactory $baseFactory;

    /**
     * UuidFactory constructor.
     *
     */
    public function __construct(?BaseUuidFactory $factory = null)
    {
        $this->baseFactory = $factory ?? BaseUuid::getFactory();
    }

    public function from(Identifier|UuidInterface $uuid): Uuid
    {
        return match(true) {
            $uuid instanceof Uuid => $uuid,
            $uuid instanceof UuidInterface => new Uuid($uuid),
            default => throw new ContractException(
                'Unexpected identifier type, received: ' . get_debug_type($uuid),
            ),
        };
    }

    public function fromBytes(string $bytes): Uuid
    {
        return new Uuid($this->baseFactory->fromBytes($bytes));
    }

    public function fromDateTime(DateTimeInterface $dateTime, ?Hexadecimal $node = null, ?int $clockSeq = null): Uuid
    {
        return new Uuid($this->baseFactory->fromDateTime($dateTime, $node, $clockSeq));
    }

    public function fromInteger(string $integer): Uuid
    {
        return new Uuid($this->baseFactory->fromInteger($integer));
    }

    public function fromString(string $uuid): Uuid
    {
        return new Uuid($this->baseFactory->fromString($uuid));
    }

    public function uuid1(
        Hexadecimal|int|string|null $node = null,
        ?int $clockSeq = null,
    ): Uuid {
        return new Uuid($this->baseFactory->uuid1($node, $clockSeq));
    }

    public function uuid2(
        int $localDomain,
        ?IntegerObject $localIdentifier = null,
        ?Hexadecimal $node = null,
        ?int $clockSeq = null,
    ): Uuid {
        return new Uuid($this->baseFactory->uuid2($localDomain, $localIdentifier, $node, $clockSeq));
    }

    public function uuid3(string|UuidInterface $ns, string $name): Uuid
    {
        return new Uuid($this->baseFactory->uuid3($ns, $name));
    }

    public function uuid4(): Uuid
    {
        return new Uuid($this->baseFactory->uuid4());
    }

    public function uuid5(string|UuidInterface $ns, string $name): Uuid
    {
        return new Uuid($this->baseFactory->uuid5($ns, $name));
    }

    public function uuid6(?Hexadecimal $node = null, ?int $clockSeq = null): Uuid
    {
        return new Uuid($this->baseFactory->uuid6($node, $clockSeq));
    }

    public function uuid7(?DateTimeInterface $dateTime = null): Uuid
    {
        if (method_exists($this->baseFactory, 'uuid7')) {
            $base = $this->baseFactory->uuid7($dateTime);
            assert($base instanceof UuidInterface);
            return new Uuid($base);
        }

        throw new RuntimeException('UUID version 7 is not supported by the underlying factory.');
    }

    public function uuid8(string $bytes): Uuid
    {
        if (method_exists($this->baseFactory, 'uuid8')) {
            $base = $this->baseFactory->uuid8($bytes);
            assert($base instanceof UuidInterface);
            return new Uuid($base);
        }

        throw new RuntimeException('UUID version 8 is not supported by the underlying factory.');
    }
}
