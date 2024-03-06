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

trait EntityTrait
{
    /**
     * @var IdentifierInterface
     */
    private IdentifierInterface $id;

    /**
     * @inheritDoc
     */
    public function getId(): IdentifierInterface
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function is(?EntityInterface $other): bool
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
    public function isNot(?EntityInterface $other): bool
    {
        return !$this->is($other);
    }
}
