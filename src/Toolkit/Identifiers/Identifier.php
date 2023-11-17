<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers;

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
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