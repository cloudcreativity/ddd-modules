<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Domain;

use CloudCreativity\Modules\Domain\EntityInterface;
use CloudCreativity\Modules\Domain\EntityTrait;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

class TestEntity implements EntityInterface
{
    use EntityTrait;

    /**
     * TestEntity constructor
     *
     * @param IdentifierInterface $id
     */
    public function __construct(IdentifierInterface $id)
    {
        $this->id = $id;
    }
}
