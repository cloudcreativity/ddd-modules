<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Domain;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;

interface Entity
{
    /**
     * Get the entity's identifier.
     *
     */
    public function getId(): ?Identifier;

    /**
     * Get the entity's identifier, or fail if one is not set.
     *
     */
    public function getIdOrFail(): Identifier;

    /**
     * Is this entity the same as the provided entity?
     *
     */
    public function is(?Entity $other): bool;

    /**
     * Is this entity not the same as the provided entity?
     *
     */
    public function isNot(?Entity $other): bool;
}
