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

use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

interface EntityInterface
{
    /**
     * Get the entity's identifier.
     *
     * @return IdentifierInterface|null
     */
    public function getId(): ?IdentifierInterface;

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
