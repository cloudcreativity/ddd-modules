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
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidFactoryInterface as BaseUuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;

final class UuidFactory implements UuidFactoryInterface
{
    /**
     * @var BaseUuidFactoryInterface
     */
    private readonly BaseUuidFactoryInterface $baseFactory;

    /**
     * UuidFactory constructor.
     *
     * @param BaseUuidFactoryInterface|null $factory
     */
    public function __construct(BaseUuidFactoryInterface $factory = null)
    {
        $this->baseFactory = $factory ?? BaseUuid::getFactory();
    }

    /**
     * @inheritDoc
     */
    public function from(UuidInterface $uuid): Uuid
    {
        return new Uuid($uuid);
    }

    /**
     * @inheritDoc
     */
    public function fromBytes(string $bytes): Uuid
    {
        return new Uuid($this->baseFactory->fromBytes($bytes));
    }

    /**
     * @inheritDoc
     */
    public function fromDateTime(DateTimeInterface $dateTime, ?Hexadecimal $node = null, ?int $clockSeq = null): Uuid
    {
        return new Uuid($this->baseFactory->fromDateTime($dateTime, $node, $clockSeq));
    }

    /**
     * @inheritDoc
     */
    public function fromInteger(string $integer): Uuid
    {
        return new Uuid($this->baseFactory->fromInteger($integer));
    }

    /**
     * @inheritDoc
     */
    public function fromString(string $uuid): Uuid
    {
        return new Uuid($this->baseFactory->fromString($uuid));
    }

    /**
     * @inheritDoc
     */
    public function uuid1(
        Hexadecimal|int|string|null $node = null,
        ?int $clockSeq = null,
    ): Uuid {
        return new Uuid($this->baseFactory->uuid1($node, $clockSeq));
    }

    /**
     * @inheritDoc
     */
    public function uuid2(
        int $localDomain,
        ?IntegerObject $localIdentifier = null,
        ?Hexadecimal $node = null,
        ?int $clockSeq = null,
    ): Uuid {
        return new Uuid($this->baseFactory->uuid2($localDomain, $localIdentifier, $node, $clockSeq));
    }

    /**
     * @inheritDoc
     */
    public function uuid3(UuidInterface|string $ns, string $name): Uuid
    {
        return new Uuid($this->baseFactory->uuid3($ns, $name));
    }

    /**
     * @inheritDoc
     */
    public function uuid4(): Uuid
    {
        return new Uuid($this->baseFactory->uuid4());
    }

    /**
     * @inheritDoc
     */
    public function uuid5(UuidInterface|string $ns, string $name): Uuid
    {
        return new Uuid($this->baseFactory->uuid5($ns, $name));
    }

    /**
     * @inheritDoc
     */
    public function uuid6(?Hexadecimal $node = null, ?int $clockSeq = null): Uuid
    {
        return new Uuid($this->baseFactory->uuid6($node, $clockSeq));
    }
}
