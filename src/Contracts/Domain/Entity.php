<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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
     * @return Identifier|null
     */
    public function getId(): ?Identifier;

    /**
     * Is this entity the same as the provided entity?
     *
     * @param Entity|null $other
     * @return bool
     */
    public function is(?Entity $other): bool;

    /**
     * Is this entity not the same as the provided entity?
     *
     * @param Entity|null $other
     * @return bool
     */
    public function isNot(?Entity $other): bool;
}
