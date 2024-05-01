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

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;

interface EntityInterface
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
     * @param EntityInterface|null $other
     * @return bool
     */
    public function is(?EntityInterface $other): bool;

    /**
     * Is this entity not the same as the provided entity?
     *
     * @param EntityInterface|null $other
     * @return bool
     */
    public function isNot(?EntityInterface $other): bool;
}
