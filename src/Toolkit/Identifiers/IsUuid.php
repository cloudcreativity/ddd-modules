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
use Ramsey\Uuid\UuidInterface;

trait IsUuid
{
    use IsIdentifier;

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function getBytes(): string
    {
        return $this->value->getBytes();
    }

    public function is(?Identifier $other): bool
    {
        if ($other instanceof IUuid) {
            return $this->value->equals($other->toBase());
        }

        return false;
    }

    public function compareTo(IUuid $other): int
    {
        return $this->value->compareTo($other->toBase());
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function key(): string
    {
        return $this->value->toString();
    }

    public function context(): string
    {
        return $this->value->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->value->toString();
    }

    public function toBase(): UuidInterface
    {
        return $this->value;
    }
}
