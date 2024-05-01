<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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
    /**
     * @var Identifier
     */
    private Identifier $id;

    /**
     * @inheritDoc
     */
    public function getId(): Identifier
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function is(?Entity $other): bool
    {
        if ($other instanceof $this) {
            return $this->id->is(
                $other->getId(),
            );
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isNot(?Entity $other): bool
    {
        return !$this->is($other);
    }
}
