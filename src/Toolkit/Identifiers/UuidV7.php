<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Uuid as IUuid;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Contracts;
use DateTimeInterface;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Rfc4122\UuidV7 as BaseUuidV7;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidInterface as IBaseUuid;

final class UuidV7 implements IUuid
{
    use IsUuid;

    public static function make(?DateTimeInterface $date = null): self
    {
        return Uuid::getFactory()->uuid7($date);
    }

    public static function from(IBaseUuid|Identifier|string|null $value): self
    {
        return self::tryFrom($value) ?? throw new ContractException(
            'Unexpected identifier type, received: ' . get_debug_type($value),
        );
    }

    public static function tryFrom(IBaseUuid|Identifier|string|null $value): ?self
    {
        $parsed = match (true) {
            $value instanceof self, $value instanceof IBaseUuid => $value,
            $value instanceof IUuid => $value->toBase(),
            is_string($value) && BaseUuid::isValid($value) => BaseUuid::getFactory()->fromString($value),
            default => null,
        };

        return match (true) {
            $parsed instanceof self => $parsed,
            $parsed instanceof BaseUuidV7, $parsed instanceof LazyUuidFromString && $parsed->getVersion() === 7 => new self($parsed),
            default => null,
        };
    }

    public function __construct(public readonly BaseUuidV7|LazyUuidFromString $value)
    {
        if ($this->value instanceof LazyUuidFromString) {
            Contracts::assert($this->value->getVersion() === 7);
        }
    }
}
