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

use CloudCreativity\Modules\Toolkit\ContractException;
use Ramsey\Uuid\UuidInterface;

final class Identifier
{
    /**
     * Make an identifier.
     *
     * @param mixed $id
     * @return IdentifierInterface
     */
    public static function make(mixed $id): IdentifierInterface
    {
        return match(true) {
            $id instanceof IdentifierInterface => $id,
            $id instanceof UuidInterface => new Uuid($id),
            is_int($id) => new IntegerId($id),
            is_string($id) => new StringId($id),
            default => throw new ContractException('Unexpected identifier type, received: ' . get_debug_type($id)),
        };
    }

    /**
     * Identifier constructor.
     */
    private function __construct()
    {
        // no-op
    }
}
