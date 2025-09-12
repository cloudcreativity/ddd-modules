<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Domain;

use CloudCreativity\Modules\Contracts\Domain\Entity;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;

trait IsEntity
{
    private readonly Identifier $id;

    public function getId(): Identifier
    {
        return $this->id;
    }

    public function getIdOrFail(): Identifier
    {
        return $this->id;
    }

    public function is(?Entity $other): bool
    {
        if ($other instanceof $this) {
            return $this->id->is(
                $other->getId(),
            );
        }

        return false;
    }

    public function isNot(?Entity $other): bool
    {
        return !$this->is($other);
    }
}
