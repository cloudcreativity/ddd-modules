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
