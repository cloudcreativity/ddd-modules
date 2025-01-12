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
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\IdentifierFactory as IIdentifierFactory;
use CloudCreativity\Modules\Toolkit\ContractException;
use Ramsey\Uuid\UuidInterface;

final class IdentifierFactory implements IIdentifierFactory
{
    /**
     * @inheritDoc
     */
    public function make(mixed $id): Identifier
    {
        return match(true) {
            $id instanceof Identifier => $id,
            $id instanceof UuidInterface => new Uuid($id),
            is_int($id) => new IntegerId($id),
            is_string($id) => new StringId($id),
            default => throw new ContractException('Unexpected identifier type, received: ' . get_debug_type($id)),
        };
    }
}
